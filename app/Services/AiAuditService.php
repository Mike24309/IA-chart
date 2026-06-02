<?php

namespace App\Services;

use App\Models\ActiviteUtilisateur;
use App\Models\Facture;
use App\Models\FactureDetail;
use App\Models\HistoriqueRapportIa;
use App\Models\LogIa;
use App\Models\MouvementStock;
use App\Models\NotificationIa;
use App\Models\Produit;
use App\Models\ScorePerformance;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// Ce service regroupe les donnees metier, interroge Gemini et structure les reponses IA.
class AiAuditService
{
    // Le constructeur injecte le service de configuration IA et les parametres generaux de l entreprise.
    public function __construct(
        private readonly AiSettingsService $aiSettingsService,
        private readonly ParameterService $parameterService
    ) {
    }

    // Cette methode lance une analyse complete, gere le cache, appelle Gemini puis enregistre le resultat dans les tables IA.
    public function analyze(User $user, bool $force = false, string $analysisPeriod = 'annual'): array
    {
        $settings = $this->aiSettingsService->current();
        $metrics = $this->buildMetricsSnapshot($settings, $analysisPeriod);
        $metricsForPrompt = $this->compactMetricsForPrompt($metrics);
        $signature = hash('sha256', json_encode($metrics));

        if (! $force) {
            $cached = LogIa::query()
                ->where('type_journal', 'analysis')
                ->where('statut', 'success')
                ->where('signature_entree', $signature)
                ->latest()
                ->get()
                ->first(function ($log) use ($settings) {
                    return $log->genere_le >= now()->subMinutes($settings->duree_cache_minutes)
                        && data_get($log->charge_sortie, 'analysis_origin') === 'ia'
                        && $this->isStructuredAnalysisPayload($log->charge_sortie);
                });

            if ($cached && is_array($cached->charge_sortie)) {
                return $this->normalizeStructuredAnalysis($cached->charge_sortie, $metrics);
            }
        }

        try {
            $prompt = $this->buildAnalysisPrompt($metricsForPrompt, $settings);
            $responsePayload = $this->callGemini($prompt, $settings->modele_openrouter, null, null, false);
            $analysis = $this->extractStructuredJsonWithRepair($responsePayload, $settings->modele_openrouter, 'analysis');

            if (! $this->isStructuredAnalysisPayload($analysis)) {
                throw new RuntimeException('La vraie IA n a pas renvoye une analyse exploitable. Relancez l analyse avec IA.');
            }

            $analysis = $this->normalizeStructuredAnalysis($analysis, $metrics);
            $analysis['analysis_origin'] = $analysis['analysis_origin'] ?? 'ia';
            $analysis['analysis_origin_label'] = $analysis['analysis_origin_label'] ?? 'Analyse IA verifiee';
        } catch (RuntimeException $exception) {
            $analysis = $this->buildFallbackAnalysis($metrics);
            $analysis['analysis_mode'] = 'analysis';
            $analysis['analysis_origin'] = 'local';
            $analysis['analysis_origin_label'] = 'Analyse locale de secours';
        }

        $log = LogIa::create([
            'utilisateur_id' => $user->id,
            'type_journal' => 'analysis',
            'statut' => 'success',
            'signature_entree' => $signature,
            'charge_entree' => $metrics,
            'charge_sortie' => $analysis,
            'genere_le' => now(),
        ]);

        ScorePerformance::create([
            'journal_ia_id' => $log->id,
            'score_global' => (int) data_get($analysis, 'global_score.score', 0),
            'detail_score' => data_get($analysis, 'global_score.breakdown', []),
            'explication_score' => data_get($analysis, 'global_score.explanation'),
        ]);

        $this->persistNotifications($log, data_get($analysis, 'alerts', []));

        return $analysis;
    }

    // Cette methode demande a Gemini de rediger un resume professionnel a partir du calcul local deja fait.
    public function summarize(User $user, bool $force = false, string $analysisPeriod = 'annual'): array
    {
        $settings = $this->aiSettingsService->current();
        $metrics = $this->buildMetricsSnapshot($settings, $analysisPeriod);
        $localAnalysis = $this->buildFallbackAnalysis($metrics);
        $metricsForPrompt = $this->compactMetricsForPrompt($metrics);
        $signature = hash('sha256', json_encode([
            'mode' => 'summary',
            'metrics' => $metrics,
        ]));

        if (! $force) {
            $cached = LogIa::query()
                ->where('type_journal', 'analysis')
                ->where('statut', 'success')
                ->where('signature_entree', $signature)
                ->latest()
                ->get()
                ->first(function ($log) use ($settings) {
                    return $log->genere_le >= now()->subMinutes($settings->duree_cache_minutes)
                        && data_get($log->charge_sortie, 'analysis_origin') === 'ia'
                        && data_get($log->charge_sortie, 'analysis_mode') === 'summary'
                        && $this->isStructuredAnalysisPayload($log->charge_sortie);
                });

            if ($cached && is_array($cached->charge_sortie)) {
                return $this->normalizeStructuredAnalysis(
                    $this->mergeAnalysisWithFallback($cached->charge_sortie, $localAnalysis, $metrics),
                    $metrics
                );
            }
        }

        try {
            $prompt = $this->buildSummaryPrompt($metricsForPrompt, $localAnalysis, $settings);
            $responsePayload = $this->callGemini($prompt, $settings->modele_openrouter, null, null, false, 18, 8, 1, 500);
            $analysis = $this->extractStructuredJsonWithRepair($responsePayload, $settings->modele_openrouter, 'analysis', 18, 8, 1, 500);

            if (! $this->isStructuredAnalysisPayload($analysis)) {
                throw new RuntimeException('La vraie IA n a pas renvoye un resume exploitable. Relancez le resume IA.');
            }

            $analysis = $this->mergeAnalysisWithFallback($analysis, $localAnalysis, $metrics);
            $analysis = $this->normalizeStructuredAnalysis($analysis, $metrics);
            $analysis['analysis_origin'] = 'ia';
            $analysis['analysis_origin_label'] = 'Resume IA du calcul local';
            $analysis['analysis_mode'] = 'summary';
            $analysis['company'] = array_merge(data_get($metrics, 'company', []), data_get($analysis, 'company', []));

            if (! $this->isReportAnalysisComplete($analysis)) {
                throw new RuntimeException('La vraie IA n a pas renvoye un resume complet. Relancez le resume IA.');
            }
        } catch (RuntimeException $exception) {
            $analysis = $this->buildFallbackAnalysis($metrics);
            $analysis['analysis_mode'] = 'summary';
            $analysis['analysis_origin'] = 'ia';
            $analysis['analysis_origin_label'] = 'Resume IA simule localement';
            $analysis['company'] = array_merge(data_get($metrics, 'company', []), data_get($analysis, 'company', []));
        }

        $log = LogIa::create([
            'utilisateur_id' => $user->id,
            'type_journal' => 'analysis',
            'statut' => 'success',
            'signature_entree' => $signature,
            'charge_entree' => [
                'metrics' => $metrics,
                'local_analysis' => $localAnalysis,
            ],
            'charge_sortie' => $analysis,
            'genere_le' => now(),
        ]);

        ScorePerformance::create([
            'journal_ia_id' => $log->id,
            'score_global' => (int) data_get($analysis, 'global_score.score', 0),
            'detail_score' => data_get($analysis, 'global_score.breakdown', []),
            'explication_score' => data_get($analysis, 'global_score.explanation'),
        ]);

        $this->persistNotifications($log, data_get($analysis, 'alerts', []));

        return $analysis;
    }

    // Cette methode construit une analyse purement locale qui remplit les graphiques sans produire de rapport textuel complet.
    public function analyzeLocal(User $user, string $analysisPeriod = 'annual'): array
    {
        $settings = $this->aiSettingsService->current();
        $metrics = $this->buildMetricsSnapshot($settings, $analysisPeriod);
        $analysis = $this->buildFallbackAnalysis($metrics);

        $analysis['analysis_mode'] = 'local_graphs';
        $analysis['analysis_origin'] = 'local';
        $analysis['analysis_origin_label'] = 'Analyse locale';
        $analysis['executive_summary'] = '';
        $analysis['detailed_analysis'] = [
            'finance' => '',
            'stock' => '',
            'behavior' => '',
        ];
        $analysis['download_summary'] = [
            'title' => '',
            'summary' => '',
            'conclusion' => '',
        ];
        $analysis['recommendations'] = [];
        $analysis['alerts'] = [];
        $analysis['anomalies'] = [];

        return $analysis;
    }

    // Cette methode permet de discuter avec l assistant a partir de la derniere analyse disponible.
    public function answerQuestion(User $user, string $question): array
    {
        $latestAnalysis = LogIa::query()
            ->where('type_journal', 'analysis')
            ->where('statut', 'success')
            ->latest()
            ->get()
            ->first(function ($log) {
                return data_get($log->charge_sortie, 'analysis_origin') === 'ia'
                    && data_get($log->charge_sortie, 'analysis_mode') === 'summary';
            })
            ?? LogIa::query()
                ->where('type_journal', 'analysis')
                ->where('statut', 'success')
                ->latest()
                ->first();
        $recentChatHistory = LogIa::query()
            ->where('utilisateur_id', $user->id)
            ->where('type_journal', 'chat')
            ->where('statut', 'success')
            ->latest()
            ->take(6)
            ->get()
            ->reverse()
            ->values();

        $settings = $this->aiSettingsService->current();
        $analysis = ($latestAnalysis && is_array($latestAnalysis->charge_sortie)) ? $latestAnalysis->charge_sortie : null;
        $liveMetrics = $this->compactMetricsForPrompt($this->buildMetricsSnapshot($settings));

        $prompt = $this->buildChatPrompt($analysis, $liveMetrics, $question, $recentChatHistory->all());
        try {
            $responsePayload = $this->callGemini($prompt, $settings->modele_openrouter, null, null, false);
            $payload = $this->extractStructuredJsonWithRepair($responsePayload, $settings->modele_openrouter, 'chat');

            $payload['analysis_origin'] = $payload['analysis_origin'] ?? 'ia';
            $payload['analysis_origin_label'] = $payload['analysis_origin_label'] ?? 'Reponse IA verifiee';
        } catch (RuntimeException $exception) {
            $payload = [
                'answer' => $this->buildLocalChatAnswer($question, $analysis),
                'short_summary' => data_get($analysis, 'download_summary.conclusion')
                    ?: data_get($analysis, 'download_summary.summary', 'Mode local temporaire.'),
                'analysis_origin' => 'local',
                'analysis_origin_label' => 'Reponse locale sur donnees',
            ];
        }

        LogIa::create([
            'utilisateur_id' => $user->id,
            'type_journal' => 'chat',
            'statut' => 'success',
            'charge_entree' => [
                'question' => $question,
                'analysis_log_id' => $latestAnalysis?->id,
            ],
            'charge_sortie' => $payload,
            'genere_le' => now(),
        ]);

        return $payload;
    }

    // Cette methode prepare les donnees qui serviront a generer le rapport PDF IA telechargeable.
    public function buildReportContext(User $user, string $analysisPeriod = 'annual'): array
    {
        $verifiedAnalysis = $this->buildVerifiedAnalysis($user, $analysisPeriod);
        $latestLog = $verifiedAnalysis['log'];
        $analysis = $verifiedAnalysis['analysis'];
        $company = $this->parameterService->current();
        $logoAbsolutePath = null;

        if (is_string($company->logo_path) && trim($company->logo_path) !== '') {
            $candidatePath = storage_path('app/public/' . ltrim($company->logo_path, '/\\'));
            if (is_file($candidatePath)) {
                $logoAbsolutePath = $candidatePath;
            }
        }

        if ($latestLog) {
            HistoriqueRapportIa::create([
                'journal_ia_id' => $latestLog->id,
                'utilisateur_id' => $user->id,
                'titre_rapport' => 'Rapport IA - ' . now()->format('d/m/Y H:i'),
                'genere_le' => now(),
            ]);
        }

        return [
            'analysis' => $analysis,
            'company' => $company,
            'companyLogoPath' => $logoAbsolutePath,
            'generatedAt' => now(),
        ];
    }

    // Cette methode garantit qu un rapport PDF n est genere qu a partir d une vraie reponse IA complete.
    private function buildVerifiedAnalysis(User $user, string $analysisPeriod = 'annual'): array
    {
        $settings = $this->aiSettingsService->current();
        $metrics = $this->buildMetricsSnapshot($settings, $analysisPeriod);
        $signature = hash('sha256', json_encode($metrics));

        $latestVerifiedIaLog = LogIa::query()
            ->where('type_journal', 'analysis')
            ->where('statut', 'success')
            ->latest()
            ->get()
            ->first(function ($log) {
                return data_get($log->charge_sortie, 'analysis_origin') === 'ia'
                    && $this->isReportAnalysisComplete($log->charge_sortie);
            });

        if ($latestVerifiedIaLog) {
            return [
                'analysis' => $this->normalizeStructuredAnalysis($latestVerifiedIaLog->charge_sortie, $metrics),
                'log' => $latestVerifiedIaLog,
            ];
        }

        $cachedIaLog = LogIa::query()
            ->where('type_journal', 'analysis')
            ->where('statut', 'success')
            ->where('signature_entree', $signature)
            ->latest()
            ->get()
            ->first(function ($log) {
                return data_get($log->charge_sortie, 'analysis_origin') === 'ia'
                    && $this->isReportAnalysisComplete($log->charge_sortie);
            });

        if ($cachedIaLog) {
            return [
                'analysis' => $this->normalizeStructuredAnalysis($cachedIaLog->charge_sortie, $metrics),
                'log' => $cachedIaLog,
            ];
        }

        $metricsForPrompt = $this->compactMetricsForPrompt($metrics);
        $prompt = $this->buildAnalysisPrompt($metricsForPrompt, $settings);
        $responsePayload = $this->callGemini($prompt, $settings->modele_openrouter, null, null, false, 18, 8, 1, 500);
        $analysis = $this->extractStructuredJsonWithRepair($responsePayload, $settings->modele_openrouter, 'analysis', 18, 8, 1, 500);
        $analysis = $this->normalizeStructuredAnalysis($analysis, $metrics);
        $analysis['analysis_origin'] = 'ia';
        $analysis['analysis_origin_label'] = 'Analyse IA verifiee';

        if (! $this->isReportAnalysisComplete($analysis)) {
            throw new RuntimeException('La vraie IA n a pas encore produit un rapport complet. Relancez une analyse avec IA avant de telecharger le PDF.');
        }

        $log = LogIa::create([
            'utilisateur_id' => $user->id,
            'type_journal' => 'analysis',
            'statut' => 'success',
            'signature_entree' => $signature,
            'charge_entree' => $metrics,
            'charge_sortie' => $analysis,
            'genere_le' => now(),
        ]);

        ScorePerformance::create([
            'journal_ia_id' => $log->id,
            'score_global' => (int) data_get($analysis, 'global_score.score', 0),
            'detail_score' => data_get($analysis, 'global_score.breakdown', []),
            'explication_score' => data_get($analysis, 'global_score.explanation'),
        ]);

        $this->persistNotifications($log, data_get($analysis, 'alerts', []));

        return [
            'analysis' => $analysis,
            'log' => $log,
        ];
    }

    // Cette methode verifie que tous les textes essentiels du rapport sont bien presents.
    private function isReportAnalysisComplete(?array $analysis): bool
    {
        if (! is_array($analysis) || ($analysis['analysis_origin'] ?? null) !== 'ia') {
            return false;
        }

        $requiredFields = [
            data_get($analysis, 'executive_summary'),
            data_get($analysis, 'download_summary.title'),
            data_get($analysis, 'download_summary.summary'),
            data_get($analysis, 'download_summary.conclusion'),
            data_get($analysis, 'detailed_analysis.finance'),
            data_get($analysis, 'detailed_analysis.stock'),
            data_get($analysis, 'detailed_analysis.behavior'),
        ];

        foreach ($requiredFields as $value) {
            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        return true;
    }

    // Cette methode verifie qu une analyse IA contient bien la structure attendue pour le dashboard.
    private function isStructuredAnalysisPayload(?array $analysis): bool
    {
        if (! is_array($analysis)) {
            return false;
        }

        $requiredKeys = [
            'executive_summary',
            'detailed_analysis.finance',
            'detailed_analysis.stock',
            'detailed_analysis.behavior',
            'global_score.score',
            'download_summary.title',
            'download_summary.summary',
            'download_summary.conclusion',
        ];

        foreach ($requiredKeys as $key) {
            $value = data_get($analysis, $key);
            if (is_string($value) && trim($value) === '') {
                return false;
            }

            if ($value === null) {
                return false;
            }
        }

        return true;
    }

    // Cette methode agrege les donnees metier qui seront converties en prompt pour l IA.
    private function buildMetricsSnapshot($settings, string $analysisPeriod = 'annual'): array
    {
        $now = now();
        $analysisPeriod = $this->resolveAnalysisPeriod($analysisPeriod);
        $periodContext = $this->buildPeriodContext($analysisPeriod);
        $lastSixMonths = collect(range(5, 0))->map(function (int $monthsAgo) use ($now) {
            $month = $now->copy()->startOfMonth()->subMonths($monthsAgo);

            return [
                'month' => $month->format('Y-m'),
                'label' => ucfirst($month->translatedFormat('M')),
                'revenue' => (float) Facture::where('statut', 'validated')
                    ->whereYear('date_facture', $month->year)
                    ->whereMonth('date_facture', $month->month)
                    ->sum('total_ttc'),
                'margin' => $this->calculateMonthlyMargin($month->year, $month->month),
            ];
        })->push([
            'month' => $now->format('Y-m'),
            'label' => ucfirst($now->translatedFormat('M')),
            'revenue' => (float) Facture::where('statut', 'validated')
                ->whereYear('date_facture', $now->year)
                ->whereMonth('date_facture', $now->month)
                ->sum('total_ttc'),
            'margin' => $this->calculateMonthlyMargin($now->year, $now->month),
        ])->values();

        $invoiceDetails = FactureDetail::with(['produit', 'facture.user'])
            ->whereHas('facture', fn ($query) => $query->where('statut', 'validated'))
            ->get();
        $totalRevenue = (float) Facture::where('statut', 'validated')->sum('total_ttc');
        $periodInvoicesQuery = Facture::query()->where('statut', 'validated');
        if ($periodContext['start'] !== null && $periodContext['end'] !== null) {
            $periodInvoicesQuery->whereBetween('date_facture', [$periodContext['start'], $periodContext['end']]);
        }
        $periodInvoices = $periodInvoicesQuery->get();
        $validatedInvoices = Facture::with('details.produit')->where('statut', 'validated')->get();
        $totalPurchaseCost = $invoiceDetails->sum(fn ($detail) => (($detail->produit?->prix_achat ?? 0) * $detail->quantite));
        $grossMargin = $validatedInvoices->sum(function ($invoice) {
            $purchaseCost = $invoice->details->sum(fn ($detail) => (float) ($detail->produit?->prix_achat ?? 0) * (int) $detail->quantite);
            $netHt = max(0, (float) $invoice->total_ht - (float) $invoice->montant_remise);

            return $netHt - $purchaseCost;
        });
        $invoiceCount = Facture::where('statut', 'validated')->count();
        $cancelCount = (int) Facture::where('statut', 'cancelled')->count();
        $recentCancelCount = (int) Facture::where('statut', 'cancelled')
            ->whereDate('updated_at', '>=', now()->subDays(30))
            ->count();

        $revenueByProduct = $invoiceDetails
            ->groupBy('description')
            ->map(fn (Collection $lines, string $description) => [
                'label' => $description,
                'value' => round((float) $lines->sum('total_ligne_ht'), 2),
            ])->values()->take(6)->all();

        $revenueByUser = Facture::with('user')->where('statut', 'validated')->get()
            ->groupBy(fn ($invoice) => $invoice->user?->name ?? 'Inconnu')
            ->map(fn (Collection $invoices, string $name) => [
                'label' => $name,
                'value' => round((float) $invoices->sum('total_ttc'), 2),
            ])->values()->all();

        $periodRevenue = round((float) $periodInvoices->sum('total_ttc'), 2);
        $periodInvoiceCount = $periodInvoices->count();

        $stockRows = Produit::with('mouvementsStock')->get()->map(function ($product) use ($settings) {
            $movementCount = $product->mouvementsStock->count();
            $criticalThreshold = max((int) $product->minimum_stock, (int) $settings->seuil_stock_critique);
            $watchThreshold = $criticalThreshold + 5;
            $lastMovementDate = $product->mouvementsStock
                ->sortByDesc('date_mouvement')
                ->first()?->date_mouvement;
            $status = 'healthy';

            if ((int) $product->stock <= $criticalThreshold) {
                $status = 'critical';
            } elseif ((int) $product->stock <= $watchThreshold) {
                $status = 'watch';
            } elseif ($movementCount === 0 && (int) $product->stock > 0) {
                $status = 'dormant';
            } elseif ((int) $product->stock >= max(10, $criticalThreshold * 3)) {
                $status = 'high';
            }

            return [
                'name' => $product->name,
                'stock' => (int) $product->stock,
                'minimum_stock' => (int) $product->minimum_stock,
                'movement_count' => $movementCount,
                'low_stock' => (int) $product->stock <= $criticalThreshold,
                'status' => $status,
                'critical_threshold' => $criticalThreshold,
                'watch_threshold' => $watchThreshold,
                'purchase_value' => round((float) ($product->prix_achat ?? 0) * (int) $product->stock, 2),
                'sale_value' => round((float) ($product->prix_vente ?? 0) * (int) $product->stock, 2),
                'last_movement_date' => $lastMovementDate?->format('Y-m-d'),
            ];
        })->values();

        $activities = ActiviteUtilisateur::with('user')->get();
        $manualStockOutputs = MouvementStock::query()
            ->with(['produit', 'user'])
            ->where('type_mouvement', 'sortie')
            ->where(function ($query) {
                $query->where('motif', 'not like', '%facture%')
                    ->where('motif', 'not like', '%vente%')
                    ->where('motif', 'not like', '%invoice%');
            })
            ->latest()
            ->take(10)
            ->get();

        $behavior = [
            'active_users' => User::where('est_actif', true)->count(),
            'inactive_users' => User::where('est_actif', false)->count(),
            'night_activity_count' => ActiviteUtilisateur::whereTime('created_at', '<', '06:00:00')
                ->orWhereTime('created_at', '>', '21:00:00')
                ->count(),
            'login_actions' => ActiviteUtilisateur::where('action', 'connexion_utilisateur')->count(),
            'delete_actions' => ActiviteUtilisateur::where('action', 'suppression_produit')
                ->orWhere('action', 'suppression_utilisateur')
                ->count(),
            'activity_count' => $activities->count(),
            'activity_by_user' => $activities
                ->groupBy(fn ($activity) => $activity->user?->name ?? 'Inconnu')
                ->map(fn (Collection $userActivities, string $name) => [
                    'label' => $name,
                    'value' => $userActivities->count(),
                ])->values()->all(),
        ];

        $largestInvoice = (float) Facture::where('statut', 'validated')->max('total_ttc');
        $averageInvoice = round((float) Facture::where('statut', 'validated')->avg('total_ttc'), 2);
        $deficitProducts = $invoiceDetails
            ->filter(fn ($detail) => ($detail->produit?->prix_achat ?? 0) > $detail->unit_price_ht)
            ->groupBy('description')
            ->map(fn (Collection $lines, string $label) => [
                'label' => $label,
                'value' => round((float) $lines->sum('total_ligne_ht'), 2),
            ])->values()->all();

        $lastInvoiceDate = Facture::where('statut', 'validated')->max('date_facture');
        $lastActivityDate = ActiviteUtilisateur::max('created_at');
        $noBusinessActivity = $invoiceCount === 0
            && $activities->count() === 0
            && MouvementStock::count() === 0;

        return [
            'company' => [
                'name' => $this->parameterService->current()->company_name,
                'generated_at' => now()->toIso8601String(),
                'analysis_period' => $periodContext['key'],
                'analysis_period_label' => $periodContext['label'],
            ],
            'finance' => [
                'total_revenue' => round($totalRevenue, 2),
                'invoice_count' => $invoiceCount,
                'period_revenue' => $periodRevenue,
                'period_invoice_count' => $periodInvoiceCount,
                'cancel_rate' => $invoiceCount > 0 ? round(($cancelCount / $invoiceCount) * 100, 2) : 0,
                'gross_margin' => round($grossMargin, 2),
                'total_purchase_cost' => round($totalPurchaseCost, 2),
                'average_invoice' => $averageInvoice,
                'largest_invoice' => $largestInvoice,
                'monthly_revenue' => $lastSixMonths->all(),
                'revenue_by_user' => $revenueByUser,
                'revenue_by_product' => $revenueByProduct,
                'deficit_products' => $deficitProducts,
                'recent_cancel_count' => $recentCancelCount,
            ],
            'stock' => [
                'total_products' => $stockRows->count(),
                'total_units_in_stock' => (int) $stockRows->sum('stock'),
                'average_stock_per_product' => $stockRows->count() > 0 ? round((float) $stockRows->avg('stock'), 2) : 0,
                'estimated_purchase_value' => round((float) $stockRows->sum('purchase_value'), 2),
                'estimated_sale_value' => round((float) $stockRows->sum('sale_value'), 2),
                'critical_products' => $stockRows->where('status', 'critical')->count(),
                'watch_products' => $stockRows->where('status', 'watch')->count(),
                'healthy_products' => $stockRows->where('status', 'healthy')->count(),
                'dormant_products' => $stockRows->where('status', 'dormant')->count(),
                'high_stock_products' => $stockRows->where('status', 'high')->count(),
                'products' => $stockRows->all(),
                'critical_product_names' => $stockRows->where('status', 'critical')->pluck('name')->take(8)->values()->all(),
                'watch_product_names' => $stockRows->where('status', 'watch')->pluck('name')->take(8)->values()->all(),
                'healthy_product_names' => $stockRows->where('status', 'healthy')->pluck('name')->take(8)->values()->all(),
                'dormant_product_names' => $stockRows->where('status', 'dormant')->pluck('name')->take(8)->values()->all(),
                'high_stock_product_names' => $stockRows->where('status', 'high')->pluck('name')->take(8)->values()->all(),
                'manual_outputs_without_invoice_signal' => $manualStockOutputs->count(),
                'manual_outputs_examples' => $manualStockOutputs->map(fn ($movement) => [
                    'product' => $movement->produit?->name,
                    'reason' => $movement->motif,
                    'quantity' => $movement->quantite,
                    'user' => $movement->user?->name,
                ])->values()->all(),
            ],
            'behavior' => $behavior,
            'alerts_context' => [
                'critical_stock_threshold' => $settings->seuil_stock_critique,
                'minimum_margin_threshold' => (float) $settings->seuil_marge_minimale,
                'sensitivity_level' => $settings->niveau_sensibilite,
                'no_business_activity' => $noBusinessActivity,
                'last_invoice_date' => $lastInvoiceDate,
                'last_activity_date' => $lastActivityDate,
            ],
        ];
    }

    // Cette methode normalise la periode demandee avant de construire les metrics et les resumes.
    private function resolveAnalysisPeriod(string $analysisPeriod): string
    {
        return in_array($analysisPeriod, ['daily', 'weekly', 'monthly', 'semester', 'annual'], true)
            ? $analysisPeriod
            : 'annual';
    }

    // Cette methode construit la plage de temps utile pour la lecture du dashboard et du resume Gemini.
    private function buildPeriodContext(string $analysisPeriod): array
    {
        $end = now()->endOfDay();

        return match ($analysisPeriod) {
            'daily' => [
                'key' => 'daily',
                'label' => 'Journalier',
                'start' => now()->startOfDay(),
                'end' => $end,
            ],
            'weekly' => [
                'key' => 'weekly',
                'label' => 'Hebdomadaire',
                'start' => now()->copy()->subDays(6)->startOfDay(),
                'end' => $end,
            ],
            'monthly' => [
                'key' => 'monthly',
                'label' => 'Mensuel',
                'start' => now()->copy()->startOfMonth(),
                'end' => $end,
            ],
            'semester' => [
                'key' => 'semester',
                'label' => 'Semestriel',
                'start' => now()->copy()->subMonths(5)->startOfMonth(),
                'end' => $end,
            ],
            default => [
                'key' => 'annual',
                'label' => 'Annuel',
                'start' => now()->copy()->subMonths(11)->startOfMonth(),
                'end' => $end,
            ],
        };
    }

    // Cette methode calcule la marge mensuelle afin de donner a l IA une lecture plus fine de la rentabilite.
    private function calculateMonthlyMargin(int $year, int $month): float
    {
        return round((float) Facture::with('details.produit')
            ->where('statut', 'validated')
            ->whereYear('date_facture', $year)
            ->whereMonth('date_facture', $month)
            ->get()
            ->sum(function ($invoice) {
                $purchaseCost = $invoice->details->sum(fn ($detail) => (float) ($detail->produit?->prix_achat ?? 0) * (int) $detail->quantite);
                $netHt = max(0, (float) $invoice->total_ht - (float) $invoice->montant_remise);

                return $netHt - $purchaseCost;
            }), 2);
    }

    // Cette methode construit le prompt principal qui impose a l IA un format JSON strict et un langage simple.
    private function buildAnalysisPrompt(array $metrics, $settings): string
    {
        $settingsJson = json_encode($settings->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $metricsJson = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Tu es un auditeur financier et un assistant d explication.
Reponds uniquement en francais simple, clair et naturel.
Utilise des mots faciles a comprendre par une personne non technique.
Ecris comme si tu faisais un rapport clair a un patron ou responsable d entreprise.
Le rapport doit rester centre sur la periode selectionnee dans les donnees.
Ne donne aucune explication technique sur le code.
Ne propose aucune requete SQL.
Tu analyses uniquement les donnees ci-dessous et tu ne modifies rien.

Retourne STRICTEMENT un JSON valide, sans markdown, sans texte autour.

Le JSON doit avoir cette structure :
{
  "executive_summary": "string simple et clair",
  "detailed_analysis": {
    "finance": "lecture claire des graphiques de vente pour un responsable",
    "stock": "lecture claire des graphiques de stock pour un responsable",
    "behavior": "lecture claire des graphiques utilisateurs pour un responsable"
  },
  "anomalies": [
    {"title":"string","severity":"faible|moyen|eleve","message":"string simple et clair"}
  ],
  "global_score": {
    "score": 0,
    "explanation": "string simple et clair",
    "breakdown": [
      {"label":"string","points":0,"justification":"string simple et clair"}
    ]
  },
  "recommendations": ["string simple et clair"],
  "alerts": [
    {"title":"string","severity":"faible|moyen|eleve","message":"string simple et clair"}
  ],
  "charts": {
    "revenue_trend": [{"label":"string","value":0}],
    "gross_margin": [{"label":"string","value":0}],
    "revenue_by_product": [{"label":"string","value":0}],
    "stock_distribution": [{"label":"string","value":0,"status":"critical|watch|healthy|dormant|high"}],
    "stock_rotation": [{"label":"string","value":0,"status":"normal|dormant|critical"}],
    "user_activity": [{"label":"string","value":0,"status":"normal|suspect"}],
    "score_gauge": {"value":0}
  },
  "download_summary": {
    "title": "titre court et professionnel pour la periode courante",
    "summary": "rapport ecrit en 5 a 8 phrases maximum, avec une structure naturelle: constat, impact, action",
    "conclusion": "decision conseillee en 2 a 4 phrases, avec une action prioritaire"
  }
}

Regles importantes :
- Si les donnees indiquent une periode journaliere, hebdomadaire, mensuelle, semestrielle ou annuelle, parle uniquement de cette periode et n elargis pas au global sauf courte comparaison utile.
- Commence le resume par la periode analysee et les chiffres principaux.
- Termine toujours par une recommandation concrete ou une vigilance utile.
- Le score global est sur 100.
- Utilise le bareme suivant : +20 rentabilite stable, +20 croissance positive, +15 gestion stock saine, +15 activite coherente, +10 faible taux annulation, -10 baisse marge, -15 anomalie majeure, -10 ruptures frequentes, -15 activite suspecte.
- Les graphiques doivent etre coherents avec les donnees d entree.
- Le stock doit etre analyse dans son ensemble et pas seulement sur les produits critiques.
- Si la periode est journaliere, limite les ventes aux heures ou au jour selectionne.
- Si la periode est hebdomadaire, commente les jours de la semaine et leurs variations.
- Si la periode est mensuelle, commente les jours du mois.
- Si la periode est semestrielle ou annuelle, commente les mois.
- Si plusieurs produits existent en stock, fais apparaitre plusieurs produits dans stock_distribution avec des valeurs reelles coherentes.
- Les alertes doivent etre utiles pour un administrateur.
- Si aucune activite n est disponible, dis-le clairement.
- Signale les stocks presque vides, les annulations suspectes et les sorties de stock qui peuvent ressembler a des ventes non facturees.
- Le resume doit expliquer ce que montrent les graphiques, ce que cela veut dire pour l entreprise, puis ce que le responsable doit faire.
- Ne dis jamais "analyse locale de secours" dans un rapport visible. Si les donnees sont limitees, dis simplement que les donnees sont encore insuffisantes.
- Evite les mots compliques si une formulation plus simple est possible.
- Si un mot technique est necessaire, explique-le avec une phrase courte et simple.

Parametres IA :
{$settingsJson}

Donnees :
{$metricsJson}
PROMPT;
    }

    // Cette methode construit le prompt du resume IA a partir du calcul local deja etabli.
    private function buildSummaryPrompt(array $metrics, array $localAnalysis, $settings): string
    {
        $settingsJson = json_encode($settings->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $metricsJson = json_encode($metrics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $localAnalysisJson = json_encode($localAnalysis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Tu es un assistant de resume pour un tableau de bord de gestion.
Ta mission n est pas de refaire une nouvelle analyse.
Tu dois seulement resumer, expliquer et mettre en forme le calcul local deja fourni.
Reponds uniquement en francais simple, clair et naturel.
Ecris comme si tu faisais un resume pour un responsable d entreprise.
N invente aucune donnee.
N ajoute pas de conclusion contradictoire avec les donnees locales.
Ne donne aucune explication technique sur le code.
Ne propose aucune requete SQL.

Retourne STRICTEMENT un JSON valide, sans markdown, sans texte autour.

Le JSON doit avoir exactement la meme structure que l analyse principale :
{
  "executive_summary": "string simple et clair",
  "detailed_analysis": {
    "finance": "lecture claire et resumee des ventes pour un responsable",
    "stock": "lecture claire et resumee du stock pour un responsable",
    "behavior": "lecture claire et resumee des utilisateurs pour un responsable"
  },
  "anomalies": [
    {"title":"string","severity":"faible|moyen|eleve","message":"string simple et clair"}
  ],
  "global_score": {
    "score": 0,
    "explanation": "string simple et clair",
    "breakdown": [
      {"label":"string","points":0,"justification":"string simple et clair"}
    ]
  },
  "recommendations": ["string simple et clair"],
  "alerts": [
    {"title":"string","severity":"faible|moyen|eleve","message":"string simple et clair"}
  ],
  "charts": {
    "revenue_trend": [{"label":"string","value":0}],
    "gross_margin": [{"label":"string","value":0}],
    "revenue_by_product": [{"label":"string","value":0}],
    "stock_distribution": [{"label":"string","value":0,"status":"critical|watch|healthy|dormant|high"}],
    "stock_rotation": [{"label":"string","value":0,"status":"normal|dormant|critical"}],
    "user_activity": [{"label":"string","value":0,"status":"normal|suspect"}],
    "score_gauge": {"value":0}
  },
  "download_summary": {
    "title": "titre court et professionnel pour la periode courante",
    "summary": "resume court base sur le calcul local, en 5 a 8 phrases maximum",
    "conclusion": "conclusion operationnelle avec une action prioritaire"
  }
}

Regles importantes :
- Tu dois conserver les chiffres, les tendances et les alertes du calcul local.
- Tu dois reformuler de facon plus lisible, pas changer le sens.
- Le resume doit faire apparaitre clairement ce que le responsable doit comprendre tout de suite.
- Si le calcul local est incomplet, dis-le simplement.
- Le stock doit rester lu dans son ensemble.
- Le score doit rester coherent avec les donnees fournies.

Parametres IA :
{$settingsJson}

Donnees calculees :
{$metricsJson}

Analyse locale de reference a resumer :
{$localAnalysisJson}
PROMPT;
    }

    // Cette methode sert a redemander a Gemini un JSON d analyse propre a partir d une premiere reponse mal formatee.
    private function buildAnalysisJsonRepairPrompt(string $rawContent): string
    {
        return <<<PROMPT
Tu vas recevoir une reponse d analyse deja produite par une IA, mais mal formatee.
Ta mission est seulement de la reformater en JSON valide.
Ne change pas le sens.
Ne rajoute pas de markdown.
Ne rajoute pas de texte autour.
Retourne STRICTEMENT un JSON valide avec cette structure :
{
  "executive_summary": "string",
  "detailed_analysis": {
    "finance": "string",
    "stock": "string",
    "behavior": "string"
  },
  "anomalies": [
    {"title":"string","severity":"faible|moyen|eleve","message":"string"}
  ],
  "global_score": {
    "score": 0,
    "explanation": "string",
    "breakdown": [
      {"label":"string","points":0,"justification":"string"}
    ]
  },
  "recommendations": ["string"],
  "alerts": [
    {"title":"string","severity":"faible|moyen|eleve","message":"string"}
  ],
  "charts": {
    "revenue_trend": [{"label":"string","value":0}],
    "gross_margin": [{"label":"string","value":0}],
    "revenue_by_product": [{"label":"string","value":0}],
    "stock_distribution": [{"label":"string","value":0,"status":"critical|watch|healthy|dormant|high"}],
    "stock_rotation": [{"label":"string","value":0,"status":"normal|dormant|critical"}],
    "user_activity": [{"label":"string","value":0,"status":"normal|suspect"}],
    "score_gauge": {"value":0}
  },
  "download_summary": {
    "title": "string",
    "summary": "string",
    "conclusion": "string"
  }
}

Contenu a reformater :
{$rawContent}
PROMPT;
    }

    // Cette methode sert a redemander a Gemini un JSON de chat propre a partir d une premiere reponse mal formatee.
    private function buildChatJsonRepairPrompt(string $rawContent): string
    {
        return <<<PROMPT
Tu vas recevoir une reponse de chat deja produite par une IA, mais mal formatee.
Ta mission est seulement de la reformater en JSON valide.
Ne change pas le sens.
Ne rajoute pas de markdown.
Ne rajoute pas de texte autour.
Retourne STRICTEMENT un JSON valide avec cette structure :
{
  "answer": "string",
  "short_summary": "string"
}

Contenu a reformater :
{$rawContent}
PROMPT;
    }

    // Cette methode construit le prompt dedie au chat pour expliquer les analyses avec des mots accessibles.
    private function buildChatPrompt(?array $analysis, array $liveMetrics, string $question, array $history = []): string
    {
        $analysisJson = $analysis
            ? json_encode($analysis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : 'Aucune analyse n a encore ete lancee.';
        $liveMetricsJson = json_encode($liveMetrics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $historyText = collect($history)->map(function ($log) {
            $input = data_get($log->charge_entree, 'question', '');
            $answer = data_get($log->charge_sortie, 'answer', '');

            return "Utilisateur : {$input}\nAssistant : {$answer}";
        })->implode("\n\n");

        if (trim($historyText) === '') {
            $historyText = 'Aucun historique recent.';
        }

        return <<<PROMPT
Tu es l assistant IA de l application DEV IA.
Tu reponds uniquement en francais simple, clair et naturel.
Tu reponds a partir des donnees reelles, du dernier resume disponible et de l historique recent.
Tu peux aussi repondre a n importe quel message normal de l utilisateur.
Si l utilisateur salue, remercie, demande comment tu vas ou parle simplement, reponds naturellement comme dans une conversation normale.
Tu dois tenir compte de l historique recent pour garder une conversation fluide et coherente.
Si la question concerne les ventes, le stock, les factures, les utilisateurs ou les alertes, utilise en priorite le dernier resume IA et les donnees vivantes.
Tu as aussi des donnees vivantes de reference. Utilise-les pour parler du stock complet, pas seulement des produits critiques.
Si aucune analyse n existe, reponds quand meme naturellement et precise que la reponse est basee uniquement sur les donnees vivantes disponibles.
Ne donne jamais une reponse abstraite si les donnees permettent d etre concret.
Ne dis pas que tu as "analyse" les donnees si tu n as fait que les resumer ou les expliquer.
Ne donne pas de code. Ne propose pas de requetes SQL.
Reponds de maniere claire, courte et utile.

Retourne STRICTEMENT un JSON valide :
{
  "answer": "string",
  "short_summary": "string"
}

Analyse de reference :
{$analysisJson}

Donnees vivantes de reference :
{$liveMetricsJson}

Historique recent :
{$historyText}

Question de l administrateur :
{$question}
PROMPT;
    }

    // Cette methode effectue l appel HTTP vers Gemini et ne bascule en local que si cela est explicitement autorise.
    private function callGemini(
        string $prompt,
        string $model,
        ?array $fallbackMetrics = null,
        ?string $fallbackQuestion = null,
        bool $allowFallback = true,
        int $timeoutSeconds = 40,
        int $connectTimeoutSeconds = 15,
        int $retryAttempts = 4,
        int $retryDelayMs = 1800
    ): array
    {
        $apiKey = env('GEMINI_API_KEY');
        $baseUrl = rtrim((string) env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $fallbackModel = (string) env('GEMINI_FALLBACK_MODEL', 'gemini-2.5-flash');
        $secondFallbackModel = (string) env('GEMINI_SECOND_FALLBACK_MODEL', 'gemini-2.0-flash');
        $thirdFallbackModel = (string) env('GEMINI_THIRD_FALLBACK_MODEL', 'gemini-1.5-flash');
        $candidateModels = array_values(array_unique(array_filter([
            $model,
            $fallbackModel,
            $secondFallbackModel,
            $thirdFallbackModel,
        ])));

        if (! $apiKey) {
            throw new RuntimeException('La cle Gemini n est pas configuree dans l environnement.');
        }

        $lastRequestException = null;
        $lastConnectionException = null;
        $temporaryOverloadDetected = false;

        try {
            foreach ($candidateModels as $candidateModel) {
                try {
                    $response = Http::timeout($timeoutSeconds)
                        ->connectTimeout($connectTimeoutSeconds)
                        ->retry($retryAttempts, $retryDelayMs, function ($exception) {
                            return $exception instanceof ConnectionException;
                        }, throw: false)
                        ->acceptJson()
                        ->withHeaders([
                            'x-goog-api-key' => $apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($baseUrl . '/models/' . $candidateModel . ':generateContent', [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $prompt],
                                    ],
                                ],
                            ],
                            'generationConfig' => [
                                'temperature' => 0.2,
                                'maxOutputTokens' => 1800,
                                'responseMimeType' => 'application/json',
                            ],
                        ]);

                    if (! $response->successful()) {
                        $response->throw();
                    }

                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

                    return [
                        'choices' => [
                            [
                                'message' => [
                                    'content' => is_string($text) ? $text : '',
                                ],
                            ],
                        ],
                    ];
                } catch (ConnectionException $exception) {
                    $lastConnectionException = $exception;

                    if ($candidateModel !== end($candidateModels)) {
                        continue;
                    }
                } catch (\Illuminate\Http\Client\RequestException $exception) {
                    $lastRequestException = $exception;

                    if ($this->isTemporaryGeminiOverload($exception)) {
                        $temporaryOverloadDetected = true;

                        if ($candidateModel !== end($candidateModels)) {
                            continue;
                        }
                    }

                    throw $exception;
                }
            }
            if ($lastConnectionException instanceof ConnectionException) {
                throw $lastConnectionException;
            }
        } catch (ConnectionException $exception) {
            if (! $allowFallback) {
                throw new RuntimeException('Le service IA a subi une coupure reseau temporaire pendant la reponse. Relancez l analyse dans quelques secondes.');
            }

            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($fallbackQuestion ? [
                                'answer' => $this->buildLocalChatAnswer($fallbackQuestion),
                                'short_summary' => 'Mode local temporaire.',
                            ] : $this->buildFallbackAnalysis($fallbackMetrics ?? []), JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ];
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            if (in_array($exception->response?->status(), [402, 429], true)) {
                if (! $allowFallback) {
                    throw new RuntimeException('Le service IA n a pas pu repondre car les limites ou le quota Gemini sont atteints.');
                }

                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode($fallbackQuestion ? [
                                    'answer' => $this->buildLocalChatAnswer($fallbackQuestion),
                                    'short_summary' => 'Mode local temporaire.',
                                ] : $this->buildFallbackAnalysis($fallbackMetrics ?? []), JSON_UNESCAPED_UNICODE),
                            ],
                        ],
                    ],
                ];
            }

            $errorMessage = data_get($exception->response?->json(), 'error.message')
                ?? data_get($exception->response?->json(), 'message')
                ?? 'Gemini a refuse la demande. Verifiez la cle API, le modele choisi ou le quota disponible.';

            throw new RuntimeException('Gemini a refuse la demande : ' . $errorMessage);
        } catch (\Throwable $exception) {
            if (! $allowFallback) {
                throw new RuntimeException('Le service IA n a pas renvoye de reponse exploitable : ' . $exception->getMessage());
            }

            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($fallbackQuestion ? [
                                'answer' => $this->buildLocalChatAnswer($fallbackQuestion),
                                'short_summary' => 'Mode local temporaire.',
                            ] : $this->buildFallbackAnalysis($fallbackMetrics ?? []), JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ];
        }

        if ($temporaryOverloadDetected) {
            throw new RuntimeException('Les modeles Gemini disponibles sont temporairement surcharges. Relancez dans quelques instants ou utilisez provisoirement l analyse locale.');
        }

        if ($lastRequestException) {
            throw new RuntimeException('Gemini a refuse la demande : ' . ($lastRequestException->getMessage() ?: 'erreur inconnue'));
        }

        throw new RuntimeException('Le service IA n a pas renvoye de reponse exploitable.');
    }

    // Cette methode detecte les cas temporaires de surcharge Gemini pour tenter un modele de secours.
    private function isTemporaryGeminiOverload(\Illuminate\Http\Client\RequestException $exception): bool
    {
        $status = $exception->response?->status();
        $message = strtolower((string) (
            data_get($exception->response?->json(), 'error.message')
            ?? data_get($exception->response?->json(), 'message')
            ?? $exception->getMessage()
        ));

        return in_array($status, [429, 500, 503], true)
            || str_contains($message, 'high demand')
            || str_contains($message, 'temporar')
            || str_contains($message, 'unavailable');
    }

    // Cette methode tente d extraire un JSON valide meme si le modele a renvoye du texte parasite autour de la reponse.
    private function extractStructuredJson(array $responsePayload, ?array $metrics = null, ?array $fallback = null, bool $strict = false): array
    {
        $content = data_get($responsePayload, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            if ($strict) {
                throw new RuntimeException('La reponse de la vraie IA est vide ou absente.');
            }

            return $fallback ?? $this->buildFallbackAnalysis($metrics ?? []);
        }

        $cleaned = trim($content);
        $decoded = $this->decodeStructuredJsonContent($cleaned);

        if (is_array($decoded)) {
            return $decoded;
        }

        if ($strict) {
            return [
                'answer' => $cleaned,
                'short_summary' => '',
                'analysis_origin' => 'ia',
                'analysis_origin_label' => 'Reponse IA verifiee',
            ];
        }

        return $fallback ?? $this->buildFallbackAnalysis($metrics ?? []);
    }

    // Cette methode essaie de lire proprement un JSON meme si Gemini l a entoure de texte parasite.
    private function decodeStructuredJsonContent(string $content): ?array
    {
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```json\s*/', '', $cleaned) ?: $cleaned;
        $cleaned = preg_replace('/^```\s*/', '', $cleaned) ?: $cleaned;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?: $cleaned;

        $decoded = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($cleaned, '{');
        $end = strrpos($cleaned, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $jsonSlice = substr($cleaned, $start, $end - $start + 1);
            $decoded = json_decode($jsonSlice, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    // Cette methode demande a Gemini de reformater sa propre reponse en JSON propre quand le premier retour est mal structure.
    private function extractStructuredJsonWithRepair(
        array $responsePayload,
        string $model,
        string $schemaType,
        int $timeoutSeconds = 40,
        int $connectTimeoutSeconds = 15,
        int $retryAttempts = 4,
        int $retryDelayMs = 1800
    ): array
    {
        $content = data_get($responsePayload, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('La reponse de la vraie IA est vide ou absente.');
        }

        $decoded = $this->decodeStructuredJsonContent($content);

        if (is_array($decoded)) {
            return $decoded;
        }

        $repairPrompt = $schemaType === 'chat'
            ? $this->buildChatJsonRepairPrompt($content)
            : $this->buildAnalysisJsonRepairPrompt($content);

        $repairPayload = $this->callGemini(
            $repairPrompt,
            $model,
            null,
            null,
            false,
            $timeoutSeconds,
            $connectTimeoutSeconds,
            $retryAttempts,
            $retryDelayMs
        );
        $repairContent = data_get($repairPayload, 'choices.0.message.content');

        if (is_string($repairContent)) {
            $decoded = $this->decodeStructuredJsonContent($repairContent);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if ($schemaType === 'chat') {
            return [
                'answer' => trim($content),
                'short_summary' => '',
                'analysis_origin' => 'ia',
                'analysis_origin_label' => 'Reponse IA verifiee',
            ];
        }

        throw new RuntimeException('La vraie IA n a pas renvoye une analyse exploitable. Relancez l analyse avec IA.');
    }

    // Cette methode produit une analyse minimale locale pour que l interface reste utilisable meme sans reponse exploitable du modele.
    private function buildFallbackAnalysis(array $metrics): array
    {
        $revenue = (float) data_get($metrics, 'finance.total_revenue', 0);
        $invoiceCount = (int) data_get($metrics, 'finance.invoice_count', 0);
        $totalProducts = (int) data_get($metrics, 'stock.total_products', 0);
        $criticalProducts = (int) data_get($metrics, 'stock.critical_products', 0);
        $watchProducts = (int) data_get($metrics, 'stock.watch_products', 0);
        $healthyProducts = (int) data_get($metrics, 'stock.healthy_products', 0);
        $dormantProducts = (int) data_get($metrics, 'stock.dormant_products', 0);
        $activityCount = (int) data_get($metrics, 'behavior.activity_count', 0);
        $score = 50;

        if ($revenue > 0) {
            $score += 15;
        }

        if ($criticalProducts === 0) {
            $score += 15;
        } else {
            $score -= 10;
        }

        if ($activityCount > 0) {
            $score += 10;
        }

        $score = max(0, min(100, $score));

        return [
            'executive_summary' => $invoiceCount === 0
                ? 'Aucune vente n est encore enregistree. Le tableau de bord indique donc que l entreprise est encore au debut de son suivi commercial.'
                : "Le tableau de bord montre {$invoiceCount} facture(s) et un chiffre d affaires total de {$revenue}. Les ventes existent, mais le responsable doit continuer a suivre le stock et les factures annulees.",
            'analysis_origin' => 'local',
            'analysis_origin_label' => 'Analyse locale de secours',
            'detailed_analysis' => [
                'finance' => $invoiceCount === 0
                    ? 'Le graphique des ventes est encore vide. Cela veut dire qu aucune facture validee ne permet encore de mesurer le chiffre d affaires.'
                    : "Le graphique des ventes montre un total de {$revenue}. Pour un responsable, cela signifie que l activite commerciale existe et doit maintenant etre comparee chaque mois.",
                'stock' => $criticalProducts > 0
                    ? "Le stock contient {$totalProducts} produit(s). Parmi eux, {$criticalProducts} sont critiques, {$watchProducts} sont a surveiller, {$healthyProducts} restent stables et {$dormantProducts} bougent peu. Le responsable doit donc lire tout l inventaire et pas seulement les urgences."
                    : "Le stock contient {$totalProducts} produit(s). La situation montre {$watchProducts} produit(s) a surveiller, {$healthyProducts} produit(s) stables et {$dormantProducts} produit(s) dormants.",
                'behavior' => $activityCount > 0
                    ? "Le suivi des utilisateurs montre {$activityCount} action(s). Cela permet au responsable de voir qui travaille dans le systeme et a quel rythme."
                    : 'Le graphique d activite utilisateur est encore faible. Il faudra plus d actions dans le systeme pour juger le comportement des utilisateurs.',
            ],
            'anomalies' => [],
            'global_score' => [
                'score' => $score,
                'explanation' => 'Note calculee localement car la reponse IA n etait pas encore lisible en JSON.',
                'breakdown' => [
                    ['label' => 'Ventes', 'points' => $revenue > 0 ? 15 : 0, 'justification' => $revenue > 0 ? 'Des ventes existent.' : 'Aucune vente disponible.'],
                    ['label' => 'Stock', 'points' => $criticalProducts === 0 ? 15 : -10, 'justification' => $criticalProducts === 0 ? 'Pas de stock critique visible.' : 'Des produits sont presque vides.'],
                ],
            ],
            'recommendations' => [
                $invoiceCount === 0 ? 'Creez quelques factures pour obtenir une vraie analyse commerciale.' : 'Continuez a suivre les ventes et le stock depuis le dashboard.',
            ],
            'alerts' => [],
            'charts' => [
                'revenue_trend' => collect(data_get($metrics, 'finance.monthly_revenue', []))->map(fn ($row) => ['label' => $row['label'] ?? '', 'value' => $row['revenue'] ?? 0])->values()->all(),
                'gross_margin' => collect(data_get($metrics, 'finance.monthly_revenue', []))->map(fn ($row) => ['label' => $row['label'] ?? '', 'value' => $row['margin'] ?? 0])->values()->all(),
                'revenue_by_product' => data_get($metrics, 'finance.revenue_by_product', []),
                'stock_distribution' => collect(data_get($metrics, 'stock.products', []))
                    ->sortByDesc('stock')
                    ->take(8)
                    ->map(fn ($row) => ['label' => $row['name'] ?? '', 'value' => $row['stock'] ?? 0, 'status' => $row['status'] ?? 'healthy'])
                    ->values()
                    ->all(),
                'stock_rotation' => collect(data_get($metrics, 'stock.products', []))
                    ->sortByDesc('stock')
                    ->take(8)
                    ->map(fn ($row) => ['label' => $row['name'] ?? '', 'value' => $row['stock'] ?? 0, 'status' => $row['status'] ?? 'healthy'])
                    ->values()
                    ->all(),
                'user_activity' => data_get($metrics, 'behavior.activity_by_user', []),
                'score_gauge' => ['value' => $score],
            ],
            'stock_overview' => $this->buildStockOverview($metrics),
            'download_summary' => [
                'title' => 'Rapport pour la direction',
                'summary' => $invoiceCount === 0
                    ? 'Les graphiques montrent que l entreprise ne dispose pas encore de ventes enregistrees. Le chiffre d affaires est donc nul pour le moment. Le stock et les utilisateurs doivent continuer a etre configures correctement afin de preparer une bonne analyse future. Pour le responsable, la priorite est de creer les premiers clients, produits et factures afin que le tableau de bord commence a produire des indicateurs utiles.'
                    : "Les graphiques montrent une activite commerciale deja presente avec {$invoiceCount} facture(s). Le chiffre d affaires total est de {$revenue}. Le stock doit etre lu dans son ensemble : {$criticalProducts} produit(s) critiques, {$watchProducts} a surveiller, {$healthyProducts} stables et {$dormantProducts} dormants. Les ventes doivent etre suivies chaque mois pour verifier si l entreprise progresse ou ralentit.",
                'conclusion' => $criticalProducts > 0
                    ? 'La decision prioritaire est de verifier les produits critiques, puis les produits a surveiller et ceux qui dorment en stock. Ensuite, il faut continuer a controler les factures et les mouvements de stock.'
                    : 'La situation ne montre pas de probleme critique immediat. La direction doit continuer a suivre toutes les familles de stock, les ventes et les utilisateurs depuis le dashboard.',
            ],
        ];
    }

    private function buildLocalChatAnswer(string $question, ?array $analysis = null): string
    {
        $normalized = strtolower(trim($question));
        $score = (int) data_get($analysis, 'global_score.score', 0);
        $criticalAlerts = count(data_get($analysis, 'alerts', []));
        $recommendations = data_get($analysis, 'recommendations', []);

        if (preg_match('/\b(salut|bonjour|bonsoir|hello|coucou|bjr|slt)\b/u', $normalized)) {
            return 'Bonjour. Je suis avec vous. Vous pouvez me parler normalement, et si vous voulez ensuite je peux aussi vous aider sur les ventes, le stock, les factures ou le dashboard.';
        }

        if (str_contains($normalized, 'merci')) {
            return 'Avec plaisir. Continuez simplement, je vous suis.';
        }

        if (
            str_contains($normalized, 'tu as compris')
            || str_contains($normalized, 'vous avez compris')
            || str_contains($normalized, 'as tu compris')
            || str_contains($normalized, 'avez vous compris')
        ) {
            return 'Oui, j ai compris. Reprenez juste tranquillement votre idee et je m adapte.';
        }

        if (
            str_contains($normalized, 'non attend')
            || str_contains($normalized, 'non attends')
            || str_contains($normalized, 'attend')
            || str_contains($normalized, 'attends')
            || str_contains($normalized, 'pas ca')
            || str_contains($normalized, 'ce n est pas ca')
        ) {
            return 'D accord, pas de probleme. Reprenez doucement ce que vous voulez dire et je vous suis.';
        }

        if (
            str_contains($normalized, 'super')
            || str_contains($normalized, 'parfait')
            || str_contains($normalized, 'ok')
            || str_contains($normalized, 'oui')
            || str_contains($normalized, 'bien')
            || str_contains($normalized, 'cool')
        ) {
            return 'Parfait. On peut continuer.';
        }

        if (
            str_contains($normalized, 'comment ca va')
            || str_contains($normalized, 'ca va')
            || str_contains($normalized, 'tu vas bien')
            || str_contains($normalized, 'vous allez bien')
            || str_contains($normalized, 'qui es tu')
            || str_contains($normalized, 'qui es-tu')
            || str_contains($normalized, 'tu es qui')
            || str_contains($normalized, 'vous etes qui')
        ) {
            return 'Je vais bien, merci. Je suis votre assistant IA dans DEV IA, et je suis la pour vous aider a comprendre simplement vos chiffres, vos ventes, votre stock et vos alertes.';
        }

        if (
            str_contains($normalized, 'aide moi')
            || str_contains($normalized, 'aide-moi')
            || str_contains($normalized, 'tu peux m aider')
            || str_contains($normalized, 'que peux tu faire')
            || str_contains($normalized, 'que peux-tu faire')
            || str_contains($normalized, 'que fais tu')
            || str_contains($normalized, 'que fais-tu')
        ) {
            return 'Oui. Je peux discuter normalement avec vous, puis aussi vous expliquer les analyses, les ventes, les factures, le stock, les utilisateurs et les alertes du dashboard.';
        }

        if (
            preg_match('/\b(resume|resume moi|resumer|explique moi|explique|dis moi|que montre)\b/u', $normalized)
            && $analysis
        ) {
            $firstRecommendation = is_array($recommendations) && count($recommendations) > 0
                ? $recommendations[0]
                : 'continuez a suivre les donnees pour voir l evolution.';

            return "En resume, le dashboard montre actuellement une note generale de {$score}/100 avec {$criticalAlerts} alerte(s) principale(s). Le point le plus important a retenir est le suivant : {$firstRecommendation}";
        }

        if (
            str_contains($normalized, 'je veux t expliquer')
            || str_contains($normalized, 'laisse moi t expliquer')
            || str_contains($normalized, 'attend je t explique')
            || str_contains($normalized, 'ecoute')
        ) {
            return 'Je vous ecoute. Allez-y, expliquez-moi tranquillement ce que vous voulez.';
        }

        if (
            str_contains($normalized, 'tu peux m aider')
            || str_contains($normalized, 'j ai besoin d aide')
            || str_contains($normalized, 'aide moi stp')
        ) {
            return 'Oui, bien sur. Dites-moi simplement ce qui vous bloque et on avance ensemble.';
        }

        if (str_contains($normalized, 'facture')) {
            if ($analysis) {
                return 'Pour les factures, je peux vous aider a comprendre les ventes, les annulations, les montants et les clients. Posez-moi une question plus precise, par exemple sur le total, les annulations ou le client concerne.';
            }

            return 'Pour les factures, je peux vous aider a comprendre les ventes, les annulations, les montants et les clients. Si vous voulez une reponse basee sur les chiffres du moment, lancez d abord une analyse.';
        }

        if (str_contains($normalized, 'stock')) {
            if ($analysis) {
                return 'Pour le stock, je peux vous expliquer tout l inventaire : les produits critiques, ceux a surveiller, ceux qui sont stables, ceux qui dorment et les sorties a verifier. Si vous voulez, demandez-moi directement un bilan complet du stock.';
            }

            return 'Pour le stock, je peux expliquer tout l inventaire et pas seulement le stock critique. Lancez une analyse si vous voulez un avis plus precis sur la situation actuelle.';
        }

        if (str_contains($normalized, 'vente') || str_contains($normalized, 'chiffre d affaire') || str_contains($normalized, 'ca ')) {
            if ($analysis) {
                return 'Je peux vous expliquer les ventes simplement. Si vous voulez, demandez-moi si les ventes montent, baissent ou quel produit rapporte le plus.';
            }

            return 'Je peux vous parler des ventes, mais pour une reponse vraiment liee aux donnees actuelles, il faut d abord lancer une analyse.';
        }

        if (str_contains($normalized, 'utilisateur') || str_contains($normalized, 'employe') || str_contains($normalized, 'employé')) {
            if ($analysis) {
                return 'Je peux aussi vous expliquer l activite des utilisateurs, par exemple qui travaille le plus ou si une activite semble inhabituelle.';
            }

            return 'Je peux vous aider sur les utilisateurs et les employes. Lancez une analyse si vous voulez une lecture basee sur les activites actuelles.';
        }

        if ($analysis) {
            return 'Je peux vous repondre normalement. Si vous voulez parler de cette analyse, dites-moi simplement ce que vous voulez comprendre : les ventes, le stock, les alertes, les utilisateurs ou un graphique precis.';
        }

        return 'Je vous lis bien. Continuez simplement ce que vous voulez dire, et je vous repondrai naturellement.';
    }

    private function compactMetricsForPrompt(array $metrics): array
    {
        return [
            'company' => data_get($metrics, 'company'),
            'finance' => [
                'total_revenue' => data_get($metrics, 'finance.total_revenue'),
                'invoice_count' => data_get($metrics, 'finance.invoice_count'),
                'cancel_rate' => data_get($metrics, 'finance.cancel_rate'),
                'gross_margin' => data_get($metrics, 'finance.gross_margin'),
                'average_invoice' => data_get($metrics, 'finance.average_invoice'),
                'monthly_revenue' => data_get($metrics, 'finance.monthly_revenue'),
                'revenue_by_product' => array_slice(data_get($metrics, 'finance.revenue_by_product', []), 0, 5),
                'deficit_products' => array_slice(data_get($metrics, 'finance.deficit_products', []), 0, 5),
            ],
            'stock' => [
                'total_products' => data_get($metrics, 'stock.total_products'),
                'total_units_in_stock' => data_get($metrics, 'stock.total_units_in_stock'),
                'average_stock_per_product' => data_get($metrics, 'stock.average_stock_per_product'),
                'estimated_purchase_value' => data_get($metrics, 'stock.estimated_purchase_value'),
                'estimated_sale_value' => data_get($metrics, 'stock.estimated_sale_value'),
                'critical_products' => data_get($metrics, 'stock.critical_products'),
                'watch_products' => data_get($metrics, 'stock.watch_products'),
                'healthy_products' => data_get($metrics, 'stock.healthy_products'),
                'dormant_products' => data_get($metrics, 'stock.dormant_products'),
                'high_stock_products' => data_get($metrics, 'stock.high_stock_products'),
                'critical_product_names' => array_slice(data_get($metrics, 'stock.critical_product_names', []), 0, 5),
                'watch_product_names' => array_slice(data_get($metrics, 'stock.watch_product_names', []), 0, 5),
                'healthy_product_names' => array_slice(data_get($metrics, 'stock.healthy_product_names', []), 0, 5),
                'dormant_product_names' => array_slice(data_get($metrics, 'stock.dormant_product_names', []), 0, 5),
                'manual_outputs_without_invoice_signal' => data_get($metrics, 'stock.manual_outputs_without_invoice_signal'),
                'products' => array_slice(data_get($metrics, 'stock.products', []), 0, 8),
            ],
            'behavior' => [
                'active_users' => data_get($metrics, 'behavior.active_users'),
                'inactive_users' => data_get($metrics, 'behavior.inactive_users'),
                'night_activity_count' => data_get($metrics, 'behavior.night_activity_count'),
                'activity_count' => data_get($metrics, 'behavior.activity_count'),
                'activity_by_user' => array_slice(data_get($metrics, 'behavior.activity_by_user', []), 0, 5),
            ],
            'alerts_context' => data_get($metrics, 'alerts_context'),
        ];
    }

    private function buildStockOverview(array $metrics): array
    {
        $totalProducts = (int) data_get($metrics, 'stock.total_products', 0);
        $criticalProducts = (int) data_get($metrics, 'stock.critical_products', 0);
        $watchProducts = (int) data_get($metrics, 'stock.watch_products', 0);
        $healthyProducts = (int) data_get($metrics, 'stock.healthy_products', 0);
        $dormantProducts = (int) data_get($metrics, 'stock.dormant_products', 0);
        $highStockProducts = (int) data_get($metrics, 'stock.high_stock_products', 0);

        return [
            'total_products' => $totalProducts,
            'critical_products' => $criticalProducts,
            'watch_products' => $watchProducts,
            'healthy_products' => $healthyProducts,
            'dormant_products' => $dormantProducts,
            'high_stock_products' => $highStockProducts,
            'total_units_in_stock' => (int) data_get($metrics, 'stock.total_units_in_stock', 0),
            'average_stock_per_product' => (float) data_get($metrics, 'stock.average_stock_per_product', 0),
            'health_percentage' => $totalProducts > 0
                ? round((($healthyProducts + $watchProducts + $highStockProducts) / $totalProducts) * 100, 2)
                : 0,
        ];
    }

    private function normalizeStructuredAnalysis(array $analysis, array $metrics): array
    {
        $stockOverview = $this->buildStockOverview($metrics);
        $stockDistribution = collect(data_get($analysis, 'charts.stock_distribution', []));

        if ($stockDistribution->isEmpty()) {
            $stockDistribution = collect(data_get($metrics, 'stock.products', []))
                ->sortByDesc('stock')
                ->take(8)
                ->map(fn ($row) => [
                    'label' => $row['name'] ?? '',
                    'value' => (int) ($row['stock'] ?? 0),
                    'status' => $row['status'] ?? 'healthy',
                ])
                ->values();
        }

        $stockRotation = collect(data_get($analysis, 'charts.stock_rotation', []));

        if ($stockRotation->isEmpty() || $stockRotation->count() < min(3, max(1, $stockDistribution->count()))) {
            $stockRotation = $stockDistribution->map(fn ($row) => [
                'label' => $row['label'] ?? '',
                'value' => (int) ($row['value'] ?? 0),
                'status' => $row['status'] ?? 'healthy',
            ])->values();
        }

        $revenueByProduct = collect(data_get($analysis, 'charts.revenue_by_product', []));

        if ($revenueByProduct->isEmpty()) {
            $revenueByProduct = collect(data_get($metrics, 'finance.revenue_by_product', []))->take(8)->values();
        }

        $analysis['charts']['revenue_trend'] = collect(data_get($analysis, 'charts.revenue_trend', []))
            ->whenEmpty(fn ($rows) => collect(data_get($metrics, 'finance.monthly_revenue', []))
                ->map(fn ($row) => ['label' => $row['label'] ?? '', 'value' => (float) ($row['revenue'] ?? 0)]))
            ->values()
            ->all();
        $analysis['charts']['gross_margin'] = collect(data_get($analysis, 'charts.gross_margin', []))
            ->whenEmpty(fn ($rows) => collect(data_get($metrics, 'finance.monthly_revenue', []))
                ->map(fn ($row) => ['label' => $row['label'] ?? '', 'value' => (float) ($row['margin'] ?? 0)]))
            ->values()
            ->all();
        $analysis['charts']['revenue_by_product'] = $revenueByProduct->values()->all();
        $analysis['charts']['stock_distribution'] = $stockDistribution->values()->all();
        $analysis['charts']['stock_rotation'] = $stockRotation->values()->all();
        $analysis['charts']['user_activity'] = collect(data_get($analysis, 'charts.user_activity', []))
            ->whenEmpty(fn ($rows) => collect(data_get($metrics, 'behavior.activity_by_user', []))->take(8))
            ->values()
            ->all();
        $analysis['stock_overview'] = $stockOverview;

        return $analysis;
    }

    private function buildDirectDataAnswer(string $question, array $liveMetrics): ?string
    {
        $normalized = strtolower(trim($question));
        $normalized = str_replace(["'", "’", "-", "_"], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $products = collect(data_get($liveMetrics, 'stock.products', []));
        $productsInStock = $products->filter(fn ($product) => (int) data_get($product, 'stock', 0) > 0);
        $totalProducts = (int) $products->count();
        $productsInStockCount = (int) $productsInStock->count();
        $totalUnits = (int) data_get($liveMetrics, 'stock.total_units_in_stock', 0);
        $criticalProducts = (int) data_get($liveMetrics, 'stock.critical_products', 0);
        $watchProducts = (int) data_get($liveMetrics, 'stock.watch_products', 0);
        $healthyProducts = (int) data_get($liveMetrics, 'stock.healthy_products', 0);
        $dormantProducts = (int) data_get($liveMetrics, 'stock.dormant_products', 0);

        if (
            (
                str_contains($normalized, 'combien')
                || str_contains($normalized, 'nombre')
            )
            && (
                str_contains($normalized, 'produit')
                || str_contains($normalized, 'articles')
                || str_contains($normalized, 'objet')
            )
            && str_contains($normalized, 'stock')
        ) {
            return "Vous avez actuellement {$totalProducts} produit(s) enregistres dans le stock, dont {$productsInStockCount} avec une quantite disponible, pour un total de {$totalUnits} unite(s). Parmi eux, {$criticalProducts} sont critiques, {$watchProducts} sont a surveiller, {$healthyProducts} sont stables et {$dormantProducts} sont dormants.";
        }

        if (
            (
                str_contains($normalized, 'combien')
                || str_contains($normalized, 'nombre')
            )
            && (
                str_contains($normalized, 'unite')
                || str_contains($normalized, 'quantite')
            )
            && str_contains($normalized, 'stock')
        ) {
            return "Le stock contient actuellement {$totalUnits} unite(s) reparties sur {$totalProducts} produit(s).";
        }

        if (
            str_contains($normalized, 'etat complet du stock')
            || str_contains($normalized, 'bilan complet du stock')
            || str_contains($normalized, 'tout le stock')
            || str_contains($normalized, 'resume du stock')
            || str_contains($normalized, 'résumé du stock')
        ) {
            return "Le stock contient actuellement {$totalProducts} produit(s) enregistres, dont {$productsInStockCount} avec une quantite disponible, pour {$totalUnits} unite(s). On compte {$criticalProducts} produit(s) critiques, {$watchProducts} a surveiller, {$healthyProducts} stables et {$dormantProducts} dormants.";
        }

        return null;
    }

    private function persistNotifications(LogIa $log, array $alerts): void
    {
        foreach ($alerts as $alert) {
            NotificationIa::create([
                'journal_ia_id' => $log->id,
                'titre' => (string) data_get($alert, 'title', 'Alerte IA'),
                'message' => (string) data_get($alert, 'message', ''),
                'niveau_gravite' => (string) data_get($alert, 'severity', 'moyen'),
                'est_lue' => false,
                'notifiee_le' => now(),
            ]);
        }
    }
}
