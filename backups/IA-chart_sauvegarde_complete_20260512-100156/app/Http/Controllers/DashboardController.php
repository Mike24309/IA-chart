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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

// Ce contrôleur prépare le tableau de bord principal Phase 1.
class DashboardController extends Controller
{
    // Cette methode calcule tous les indicateurs necessaires au dashboard admin et a la vue operationnelle employe.
    public function index(): View
    {
        $currentUser = auth()->user();
        $validatedInvoices = Facture::where('statut', 'validated');
        $totalRevenue = (clone $validatedInvoices)->sum('total_ttc');
        $monthlyRevenue = (clone $validatedInvoices)->whereMonth('date_facture', now()->month)
            ->whereYear('date_facture', now()->year)
            ->sum('total_ttc');
        $invoiceCount = Facture::count();
        $employeeInvoiceCount = Facture::where('utilisateur_id', $currentUser->id)->count();
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
            ? MouvementStock::with(['produit', 'user'])->latest()->take(8)->get()
            : collect();
        $lowStockProducts = Produit::whereColumn('stock', '<=', 'stock_minimum')
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('est_actif', true))
            ->take(8)
            ->get();
        $stockProducts = Produit::query()
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('est_actif', true))
            ->get();
        $recentInvoices = Facture::with(['client', 'user'])
            ->when(! $currentUser->isAdministrator(), fn ($query) => $query->where('utilisateur_id', $currentUser->id))
            ->latest()
            ->take(6)
            ->get();
        $recentActivities = ActiviteUtilisateur::with('user')->latest()->take(6)->get();
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

        $topProducts = FactureDetail::query()
            ->select('produit_id', 'description')
            ->selectRaw('SUM(quantite) as total_quantity')
            ->selectRaw('SUM(total_ligne_ht) as total_amount')
            ->whereHas('facture', fn ($query) => $query->where('statut', 'validated'))
            ->groupBy('produit_id', 'description')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

        $topUsers = User::query()
            ->withSum(['factures as total_revenue' => fn ($query) => $query->where('statut', 'validated')], 'total_ttc')
            ->withCount('factures')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        $monthlySeries = $this->buildMonthlySeries();
        $maxMonthlyAmount = (float) $monthlySeries->max('amount');
        $maxProductAmount = (float) $topProducts->max('total_amount');
        $maxUserAmount = (float) $topUsers->max('total_revenue');
        $previousMonthRevenue = (float) ($monthlySeries->slice(-2, 1)->first()['amount'] ?? 0);
        $monthlyGrowthRate = $previousMonthRevenue > 0
            ? round((($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 2)
            : ($monthlyRevenue > 0 ? 100.0 : 0.0);
        $cancelRate = $invoiceCount > 0 ? round((Facture::where('statut', 'cancelled')->count() / $invoiceCount) * 100, 2) : 0;
        $grossMargin = $this->calculateGrossMargin();
        $averageTicket = (float) Facture::where('statut', 'validated')->avg('total_ttc');
        $aiKpis = [
            'score' => 0,
            'cancelRate' => $cancelRate,
            'stockHealth' => $productCount > 0 ? round((($productCount - $lowStockCount) / $productCount) * 100, 2) : 0,
            'activeUserRate' => User::count() > 0 ? round(($activeUsers / User::count()) * 100, 2) : 0,
            'grossMargin' => round($grossMargin, 2),
            'averageTicket' => round($averageTicket, 2),
        ];

        $categoryPerformance = Category::with('produits.factureDetails.facture')->get()->map(function ($category) {
            $revenue = $category->produits->sum(fn ($product) => $product->factureDetails
                ->filter(fn ($detail) => $detail->facture?->statut === 'validated')
                ->sum('total_ligne_ht'));

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
            ? round((($stockOverview['healthy_products'] + $stockOverview['watch_products']) / $stockOverview['total_products']) * 100, 2)
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
            ->map(fn ($product) => [
                'label' => $product->description,
                'value' => round((float) $product->total_amount, 2),
            ])
            ->values();

        return view('dashboard.index', compact(
            'totalRevenue',
            'monthlyRevenue',
            'invoiceCount',
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
            'monthlySeries',
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
            'revenueByProductSeed'
        ));
    }

    // Cette methode construit la serie mensuelle utilisee dans les graphiques d evolution du chiffre d affaires.
    private function buildMonthlySeries(): Collection
    {
        $months = collect(range(11, 0))->map(function (int $monthsAgo): array {
            $monthDate = now()->startOfMonth()->subMonths($monthsAgo);

            return [
                'key' => $monthDate->format('Y-m'),
                'label' => ucfirst($monthDate->translatedFormat('M')),
                'amount' => 0,
            ];
        })->values();

        $indexedTotals = Facture::query()
            ->selectRaw('YEAR(date_facture) as invoice_year')
            ->selectRaw('MONTH(date_facture) as invoice_month')
            ->selectRaw('SUM(total_ttc) as total_amount')
            ->where('statut', 'validated')
            ->whereDate('date_facture', '>=', now()->startOfMonth()->subMonths(11))
            ->groupBy('invoice_year', 'invoice_month')
            ->orderBy('invoice_year')
            ->orderBy('invoice_month')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = Carbon::createFromDate((int) $row->invoice_year, (int) $row->invoice_month, 1)->format('Y-m');

                return [$key => (float) $row->total_amount];
            });

        return $months->map(function (array $month) use ($indexedTotals): array {
            $month['amount'] = $indexedTotals[$month['key']] ?? 0;

            return $month;
        });
    }

    // Cette methode calcule la marge brute globale a partir des factures validees et du cout d achat des produits vendus.
    private function calculateGrossMargin(): float
    {
        return round((float) Facture::with('details.produit')
            ->where('statut', 'validated')
            ->get()
            ->sum(function ($invoice) {
                $purchaseCost = $invoice->details->sum(fn ($detail) => (float) ($detail->produit?->prix_achat ?? 0) * (int) $detail->quantite);
                $netHt = max(0, (float) $invoice->total_ht - (float) $invoice->montant_remise);

                return $netHt - $purchaseCost;
            }), 2);
    }
}
