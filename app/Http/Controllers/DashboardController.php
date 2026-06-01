<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ActiviteUtilisateur;
use App\Models\Category;
use App\Models\Facture;
use App\Models\FactureDetail;
use App\Models\MouvementStock;
use App\Models\NotificationIa;
use App\Models\Produit;
use App\Models\User;
use App\Services\ParameterService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ce contrôleur prépare le tableau de bord principal Phase 1.
class DashboardController extends Controller
{
    public function __construct(
        private readonly ParameterService $parameterService
    ) {
    }

    // Cette methode calcule tous les indicateurs necessaires au dashboard admin et a la vue operationnelle employe.
    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        $settings = $this->parameterService->current();
        $systemCurrency = strtoupper((string) ($settings->currency ?? 'USD'));
        $exchangeRate = max(1, (float) ($settings->usd_to_fc_rate ?? 2500));
        $analysisPeriod = $this->resolveAnalysisPeriod($request->string('analysis_period')->toString());
        $periodContext = $this->buildPeriodContext($analysisPeriod);
        $validatedInvoices = Facture::where('statut', 'validated')->get();
        $validatedInvoicesInPeriod = $this->applyDateRangeToFactureQuery(Facture::where('statut', 'validated'), $periodContext)->get();
        $totalRevenue = $this->sumNormalizedInvoiceAmounts($validatedInvoices, $systemCurrency);
        $periodRevenue = $this->sumNormalizedInvoiceAmounts($validatedInvoicesInPeriod, $systemCurrency);
        $monthlyRevenue = $periodRevenue;
        $analysisPeriodLabel = $periodContext['label'];
        $invoiceCount = $validatedInvoices->count();
        $periodInvoiceCount = $validatedInvoicesInPeriod->count();
        $employeeInvoiceCount = $this->applyDateRangeToFactureQuery(
            Facture::where('utilisateur_id', $currentUser->id),
            $periodContext
        )->count();
        $productCount = $currentUser->isAdministrator() ? Produit::count() : Produit::where('est_actif', true)->count();
        $clientCount = Client::count();
        $lowStockCount = Produit::query()
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('est_actif', true))
            ->get()
            ->filter
            ->isBelowMinimum()
            ->count();
        $activeUsers = User::where('est_actif', true)->count();
        $latestMovements = $currentUser->isAdministrator()
            ? $this->applyDateRangeToMovementQuery(MouvementStock::with(['produit', 'user']), $periodContext)->latest()->take(8)->get()
            : collect();
        $lowStockProducts = Produit::whereColumn('stock', '<=', 'stock_minimum')
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('est_actif', true))
            ->take(8)
            ->get();
        $stockProducts = Produit::query()
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('est_actif', true))
            ->get();
        $recentInvoices = Facture::with(['client', 'user'])
            ->where('statut', 'validated')
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('utilisateur_id', $currentUser->id))
            ->tap(fn ($query) => $this->applyDateRangeToFactureQuery($query, $periodContext))
            ->latest()
            ->take(6)
            ->get();
        $recentActivities = $this->applyDateRangeToActivityQuery(ActiviteUtilisateur::with('user'), $periodContext)->latest()->take(6)->get();
        $recentAiNotifications = $currentUser->isAdministrator()
            ? NotificationIa::with('log')->latest('notifiee_le')->take(24)->get()
                ->filter(fn ($notification) => data_get($notification->log?->charge_sortie, 'analysis_origin') === 'ia')
                ->take(6)
                ->values()
            : collect();
        $unreadAiNotifications = $currentUser->isAdministrator()
            ? NotificationIa::with('log')->where('est_lue', false)->latest('notifiee_le')->get()
                ->filter(fn ($notification) => data_get($notification->log?->charge_sortie, 'analysis_origin') === 'ia')
                ->count()
            : 0;

        $periodDetails = FactureDetail::with(['facture', 'produit'])
            ->whereHas('facture', fn ($query) => $this->applyDateRangeToFactureQuery($query->where('statut', 'validated'), $periodContext))
            ->get();
        $topProducts = $periodDetails
            ->groupBy('description')
            ->map(function (Collection $lines, string $description) use ($systemCurrency) {
                $sample = $lines->first();

                return [
                    'produit_id' => $sample?->produit_id,
                    'description' => $description,
                    'total_quantity' => (int) $lines->sum('quantite'),
                    'total_amount' => round($lines->sum(fn ($detail) => $detail->facture?->normalizeAmountToSystemCurrency((float) $detail->total_ligne_ht, $systemCurrency) ?? (float) $detail->total_ligne_ht), 2),
                ];
            })
            ->sortByDesc('total_amount')
            ->take(5)
            ->values();

        $topUsers = User::with(['factures' => fn ($query) => $this->applyDateRangeToFactureQuery($query->where('statut', 'validated'), $periodContext)])
            ->get()
            ->map(function (User $user) use ($systemCurrency) {
                $factures = $user->factures;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_revenue' => round($factures->sum(fn ($invoice) => $invoice->normalizeAmountToSystemCurrency((float) $invoice->total_ttc, $systemCurrency)), 2),
                    'factures_count' => $factures->count(),
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(5)
            ->values();

        $periodSeries = $this->buildRevenueSeries($analysisPeriod, $periodContext, $systemCurrency);
        $maxMonthlyAmount = (float) $periodSeries->max('amount');
        $maxProductAmount = (float) $topProducts->max('total_amount');
        $maxUserAmount = (float) $topUsers->max('total_revenue');
        $previousMonthRevenue = (float) ($periodSeries->slice(-2, 1)->first()['amount'] ?? 0);
        $monthlyGrowthRate = $previousMonthRevenue > 0
            ? round((($periodRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 2)
            : ($periodRevenue > 0 ? 100.0 : 0.0);
        $cancelCountInPeriod = $this->applyDateRangeToFactureQuery(Facture::where('statut', 'cancelled'), $periodContext)->count();
        $cancelRate = $periodInvoiceCount > 0 ? round(($cancelCountInPeriod / $periodInvoiceCount) * 100, 2) : 0;
        $grossMargin = $this->calculateGrossMargin($periodContext, $systemCurrency, $exchangeRate);
        $averageTicket = $periodInvoiceCount > 0 ? round($periodRevenue / $periodInvoiceCount, 2) : 0;
        $aiKpis = [
            'score' => 0,
            'cancelRate' => $cancelRate,
            'stockHealth' => $productCount > 0 ? round((($productCount - $lowStockCount) / $productCount) * 100, 2) : 0,
            'activeUserRate' => User::count() > 0 ? round(($activeUsers / User::count()) * 100, 2) : 0,
            'grossMargin' => round($grossMargin, 2),
            'averageTicket' => round($averageTicket, 2),
        ];

        $categoryPerformance = Category::with('produits.factureDetails.facture')->get()->map(function ($category) use ($periodContext, $systemCurrency) {
            $revenue = $category->produits->sum(function ($product) use ($periodContext, $systemCurrency) {
                return $product->factureDetails
                    ->filter(fn ($detail) => $detail->facture?->statut === 'validated'
                        && $detail->facture?->date_facture
                        && Carbon::parse($detail->facture->date_facture)->between($periodContext['start'], $periodContext['end']))
                    ->sum(fn ($detail) => $detail->facture?->normalizeAmountToSystemCurrency((float) $detail->total_ligne_ht, $systemCurrency) ?? (float) $detail->total_ligne_ht);
            });

            return [
                'label' => $category->nom,
                'value' => round((float) $revenue, 2),
            ];
        })->sortByDesc('value')->take(6)->values();

        $categoryStock = Category::with('produits')->get()->map(function ($category) {
            $products = $category->produits;
            $healthy = $products->filter(fn ($product) => $product->stock > $product->stock_minimum)->count();
            $total = max(1, $products->count());

            return [
                'label' => $category->nom,
                'value' => round(($healthy / $total) * 100, 2),
            ];
        })->take(6)->values();

        $topCategory = $categoryPerformance->first();
        $stockAlertRate = $productCount > 0 ? round(($lowStockCount / $productCount) * 100, 2) : 0;
        $stockOverview = [
            'total_products' => $stockProducts->count(),
            'critical_products' => $stockProducts->filter(fn ($product) => $product->stock <= $product->stock_minimum)->count(),
            'watch_products' => $stockProducts->filter(fn ($product) => $product->stock > $product->stock_minimum && $product->stock <= ($product->stock_minimum + 5))->count(),
            'healthy_products' => $stockProducts->filter(fn ($product) => $product->stock > ($product->stock_minimum + 5))->count(),
            'total_units' => (int) $stockProducts->sum('stock'),
        ];
        $stockOverview['health_percentage'] = $stockOverview['total_products'] > 0
            ? round(($stockOverview['healthy_products'] / $stockOverview['total_products']) * 100, 2)
            : 0;
        $stockDistribution = $stockProducts
            ->filter(fn ($product) => (int) $product->stock > 0)
            ->sortByDesc('stock')
            ->take(8)
            ->map(fn ($product) => [
                'label' => $product->name,
                'value' => (int) $product->stock,
                'status' => $product->stock <= $product->stock_minimum
                    ? 'critical'
                    : ($product->stock <= ($product->stock_minimum + 5) ? 'watch' : 'healthy'),
            ])
            ->values();
        $revenueByProductSeed = $topProducts
            ->map(fn (array $product) => [
                'label' => $product['description'],
                'value' => round((float) $product['total_amount'], 2),
            ])
            ->values();

        return view('dashboard.index', compact(
            'totalRevenue',
            'monthlyRevenue',
            'periodRevenue',
            'invoiceCount',
            'periodInvoiceCount',
            'employeeInvoiceCount',
            'productCount',
            'clientCount',
            'lowStockCount',
            'activeUsers',
            'latestMovements',
            'lowStockProducts',
            'recentInvoices',
            'recentActivities',
            'recentAiNotifications',
            'unreadAiNotifications',
            'topProducts',
            'topUsers',
            'periodSeries',
            'maxMonthlyAmount',
            'maxProductAmount',
            'maxUserAmount',
            'aiKpis',
            'categoryPerformance',
            'categoryStock',
            'monthlyGrowthRate',
            'stockAlertRate',
            'topCategory',
            'stockOverview',
            'stockDistribution',
            'revenueByProductSeed',
            'analysisPeriod',
            'analysisPeriodLabel',
            'periodContext'
        ));
    }

    // Cette methode construit la serie temporelle qui alimente les graphiques selon la periode choisie.
    private function buildRevenueSeries(string $analysisPeriod, array $periodContext, string $systemCurrency): Collection
    {
        $invoices = $this->applyDateRangeToFactureQuery(
            Facture::where('statut', 'validated'),
            $periodContext
        )->get();

        if ($analysisPeriod === 'daily') {
            $hours = collect(range(0, 23))->map(function (int $hour): array {
                return [
                    'key' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
                    'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . 'h',
                    'amount' => 0,
                ];
            })->values();

            $indexedTotals = $invoices
                ->groupBy(fn ($invoice) => Carbon::parse($invoice->created_at)->format('H'))
                ->map(fn (Collection $group) => round($group->sum(fn ($invoice) => $invoice->normalizeAmountToSystemCurrency((float) $invoice->total_ttc, $systemCurrency)), 2));

            return $hours->map(function (array $hour) use ($indexedTotals): array {
                $hour['amount'] = $indexedTotals[$hour['key']] ?? 0;

                return $hour;
            });
        }

        if ($analysisPeriod === 'weekly') {
            $days = collect(range(6, 0))->map(function (int $daysAgo) {
                $date = now()->copy()->startOfDay()->subDays($daysAgo);

                return [
                    'key' => $date->format('Y-m-d'),
                    'label' => ucfirst($date->translatedFormat('D')),
                    'amount' => 0,
                ];
            })->values();

            $indexedTotals = $invoices
                ->groupBy(fn ($invoice) => Carbon::parse($invoice->date_facture)->toDateString())
                ->map(fn (Collection $group) => round($group->sum(fn ($invoice) => $invoice->normalizeAmountToSystemCurrency((float) $invoice->total_ttc, $systemCurrency)), 2));

            return $days->map(function (array $day) use ($indexedTotals): array {
                $day['amount'] = $indexedTotals[$day['key']] ?? 0;

                return $day;
            });
        }

        if ($analysisPeriod === 'monthly') {
            $days = collect(range(29, 0))->map(function (int $daysAgo) {
                $date = now()->copy()->startOfDay()->subDays($daysAgo);

                return [
                    'key' => $date->format('Y-m-d'),
                    'label' => $date->format('d'),
                    'amount' => 0,
                ];
            })->values();

            $indexedTotals = $invoices
                ->groupBy(fn ($invoice) => Carbon::parse($invoice->date_facture)->toDateString())
                ->map(fn (Collection $group) => round($group->sum(fn ($invoice) => $invoice->normalizeAmountToSystemCurrency((float) $invoice->total_ttc, $systemCurrency)), 2));

            return $days->map(function (array $day) use ($indexedTotals): array {
                $day['amount'] = $indexedTotals[$day['key']] ?? 0;

                return $day;
            });
        }

        $monthsBack = $analysisPeriod === 'semester' ? 5 : 11;
        $months = collect(range($monthsBack, 0))->map(function (int $monthsAgo): array {
            $monthDate = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'key' => $monthDate->format('Y-m'),
                'label' => ucfirst($monthDate->translatedFormat('M')),
                'amount' => 0,
            ];
        })->values();

        $indexedTotals = $invoices
            ->groupBy(fn ($invoice) => Carbon::parse($invoice->date_facture)->format('Y-m'))
            ->map(fn (Collection $group) => round($group->sum(fn ($invoice) => $invoice->normalizeAmountToSystemCurrency((float) $invoice->total_ttc, $systemCurrency)), 2));

        return $months->map(function (array $month) use ($indexedTotals): array {
            $month['amount'] = $indexedTotals[$month['key']] ?? 0;

            return $month;
        });
    }

    // Cette methode calcule la marge brute globale a partir des factures validees et du cout d achat des produits vendus.
    private function calculateGrossMargin(array $periodContext, string $systemCurrency, float $exchangeRate): float
    {
        return round((float) $this->applyDateRangeToFactureQuery(
            Facture::with('details.produit')->where('statut', 'validated'),
            $periodContext
        )
            ->get()
            ->sum(function ($invoice) use ($systemCurrency, $exchangeRate) {
                $purchaseCost = $invoice->details->sum(fn ($detail) => $this->normalizeBaseAmountToSystemCurrency((float) ($detail->produit?->prix_achat ?? 0) * (int) $detail->quantite, $systemCurrency, $exchangeRate));
                $netHt = max(0, $invoice->normalizeAmountToSystemCurrency((float) $invoice->total_ht - (float) $invoice->montant_remise, $systemCurrency));

                return $netHt - $purchaseCost;
            }), 2);
    }

    // Cette methode normalise la periode analysee afin d eviter les valeurs invalides dans l URL.
    private function resolveAnalysisPeriod(string $analysisPeriod): string
    {
        return in_array($analysisPeriod, ['daily', 'weekly', 'monthly', 'semester', 'annual'], true)
            ? $analysisPeriod
            : 'annual';
    }

    // Cette methode retourne la plage de dates et son libelle lisible pour le dashboard admin.
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
                'start' => now()->copy()->subDays(29)->startOfDay(),
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

    // Cette methode applique la meme plage de dates a toutes les requetes facture du dashboard.
    private function applyDateRangeToFactureQuery($query, array $periodContext)
    {
        return $query->whereBetween('date_facture', [$periodContext['start'], $periodContext['end']]);
    }

    // Cette methode applique la periode choisie aux mouvements de stock affiches sur le dashboard.
    private function applyDateRangeToMovementQuery($query, array $periodContext)
    {
        return $query->whereBetween('date_mouvement', [$periodContext['start']->toDateString(), $periodContext['end']->toDateString()]);
    }

    // Cette methode applique la periode choisie au journal d activite.
    private function applyDateRangeToActivityQuery($query, array $periodContext)
    {
        return $query->whereBetween('created_at', [$periodContext['start'], $periodContext['end']]);
    }

    // Cette methode convertit et additionne les montants facture dans la devise de reference du tableau de bord.
    private function sumNormalizedInvoiceAmounts(Collection $invoices, string $systemCurrency, string $field = 'total_ttc'): float
    {
        return round($invoices->sum(fn ($invoice) => $invoice->normalizeAmountToSystemCurrency((float) data_get($invoice, $field, 0), $systemCurrency)), 2);
    }

    // Cette methode normalise un montant base USD vers la devise de reference du dashboard.
    private function normalizeBaseAmountToSystemCurrency(float $amount, string $systemCurrency, float $exchangeRate): float
    {
        return $systemCurrency === 'FC' ? round($amount * $exchangeRate, 2) : round($amount, 2);
    }
}
