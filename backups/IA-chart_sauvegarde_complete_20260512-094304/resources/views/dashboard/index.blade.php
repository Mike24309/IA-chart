@extends('layouts.app')
{{-- Cette vue affiche le tableau de bord principal avec une version admin et une version employe. --}}

@section('page-title', 'Dashboard')
@section('page-description', 'Pilotage commercial, stock et activite operationnelle')
@section('content')
@php
    // Cette preparation convertit les notifications IA en tableau simple pour le JavaScript.
    $dashboardAiNotifications = $recentAiNotifications->map(function ($notification) {
        return [
            'title' => $notification->titre,
            'message' => $notification->message,
            'severity' => $notification->niveau_gravite,
        ];
    })->values();
@endphp
{{-- Ce bandeau affiche le resume rapide du role courant. --}}
<section class="hero-banner {{ auth()->user()->isAdministrator() ? 'hero-admin' : 'hero-employee' }}">
    <div>
        {{-- Ce texte change selon le role. Modifiez ici le message d accueil du dashboard. --}}
        <span class="eyebrow">{{ auth()->user()->isAdministrator() ? 'Vue direction' : 'Vue operationnelle' }}</span>
        <h3>{{ auth()->user()->isAdministrator() ? 'Centre de controle administrateur' : 'Espace employe oriente execution' }}</h3>
        <p>
            {{ auth()->user()->isAdministrator()
                ? 'Surveillez le chiffre d affaires, le stock disponible, les utilisateurs actifs et les dernieres operations realisees.'
                : 'Creez vos factures, verifiez le stock disponible et accedez rapidement a votre profil.' }}
        </p>
    </div>
    <div class="hero-metrics">
        @if(auth()->user()->isAdministrator())
            <div>
                <span>CA du mois</span>
                <strong>{{ number_format($monthlyRevenue, 2) }} {{ $appSettings->currency }}</strong>
            </div>
            <div>
                <span>Clients</span>
                <strong>{{ $clientCount }}</strong>
            </div>
            <div>
                <span>Stocks a verifier</span>
                <strong>{{ $lowStockCount }}</strong>
            </div>
        @else
            <div>
                <span>Mes factures</span>
                <strong>{{ $employeeInvoiceCount }}</strong>
            </div>
            <div>
                <span>Produits actifs</span>
                <strong>{{ $productCount }}</strong>
            </div>
            <div>
                <span>Stocks a verifier</span>
                <strong>{{ $lowStockCount }}</strong>
            </div>
        @endif
    </div>
</section>

@if(! auth()->user()->isAdministrator())
{{-- Cette partie affiche le dashboard simplifie reserve a l employe. --}}
<section class="stats-grid employee-quick-grid">
    <article class="card stat-card employee-stat-card"><span>Action principale</span><strong><span class="stat-card-value">Vendre</span></strong><a href="{{ route('invoices.create') }}" class="btn btn-primary">Nouvelle facture</a></article>
    <article class="card stat-card employee-stat-card"><span>Mes factures</span><strong><span class="stat-card-value">{{ $employeeInvoiceCount }}</span></strong><a href="{{ route('invoices.index') }}" class="btn btn-secondary">Voir mes factures</a></article>
    <article class="card stat-card employee-stat-card"><span>Stock disponible</span><strong><span class="stat-card-value">{{ $productCount }}</span></strong><a href="{{ route('products.index') }}" class="btn btn-secondary">Consulter le stock</a></article>
    <article class="card stat-card employee-stat-card"><span>Mon compte</span><strong><span class="stat-card-value">Profil</span></strong><a href="{{ route('profile.edit') }}" class="btn btn-secondary">Modifier mon profil</a></article>
</section>

<section class="content-grid dashboard-detail-grid employee-module-grid">
    <details class="card dashboard-module-toggle dashboard-module-card employee-module-card">
        <summary>
            <div>
                <strong>Mes dernieres factures</strong>
                <span class="muted">Ouvrir le detail de vos dernieres ventes</span>
            </div>
            <span class="status-badge status-info">{{ count($recentInvoices) }}</span>
        </summary>
        <div class="dashboard-module-body table-responsive">
            <table>
                <thead><tr><th>Numero</th><th>Client</th><th>Date</th><th>Total TTC</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($recentInvoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->client->name }}</td>
                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                        <td>{{ number_format($invoice->total_ttc, 2) }} {{ $appSettings->currency }}</td>
                        <td><a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Vous n avez pas encore cree de facture.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </details>

    <details class="card dashboard-module-toggle dashboard-module-card employee-module-card">
        <summary>
            <div>
                <strong>Stock disponible a surveiller</strong>
                <span class="muted">Ouvrir le detail des produits a surveiller</span>
            </div>
            <span class="status-badge status-danger">{{ count($lowStockProducts) }}</span>
        </summary>
        <div class="dashboard-module-body table-responsive">
            <table>
                <thead><tr><th>Produit</th><th>Reference</th><th>Stock</th><th>Minimum</th></tr></thead>
                <tbody>
                @forelse($lowStockProducts as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->reference }}</td>
                        <td><span class="status-badge status-danger">{{ $product->stock }}</span></td>
                        <td>{{ $product->minimum_stock }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucun stock critique pour le moment.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </details>
</section>
@else
{{-- Cette partie affiche les indicateurs globaux reserves a l administrateur. --}}
<section class="stats-grid">
    <article class="card stat-card">
        <span>CA global</span>
        <strong>
            <span class="stat-card-value">{{ number_format($totalRevenue, 2) }}</span>
            <span class="stat-card-currency">{{ $appSettings->currency }}</span>
        </strong>
    </article>
    <article class="card stat-card">
        <span>CA mensuel</span>
        <strong>
            <span class="stat-card-value">{{ number_format($monthlyRevenue, 2) }}</span>
            <span class="stat-card-currency">{{ $appSettings->currency }}</span>
        </strong>
    </article>
    <article class="card stat-card"><span>Factures</span><strong><span class="stat-card-value">{{ $invoiceCount }}</span></strong></article>
    <article class="card stat-card"><span>Produits</span><strong><span class="stat-card-value">{{ $productCount }}</span></strong></article>
    <article class="card stat-card"><span>Stock critique</span><strong><span class="stat-card-value">{{ $lowStockCount }}</span></strong></article>
    <article class="card stat-card"><span>Utilisateurs actifs</span><strong><span class="stat-card-value">{{ $activeUsers }}</span></strong></article>
</section>

<section class="card dashboard-ai-board">
    <div class="dashboard-ai-header">
        <div>
            <span class="eyebrow">Analyse IA</span>
            <h3>Espace d analyse principal</h3>
            <p class="muted">Choisissez vous-meme le type de graphique et la donnee a afficher dans un seul espace central, plus propre et plus lisible.</p>
            <div class="dashboard-ai-tags">
                <span class="pill">Ventes</span>
                <span class="pill">Stock</span>
                <span class="pill">Utilisateurs</span>
            </div>
        </div>
        <div class="dashboard-ai-toolbar">
            <details class="ai-bell-menu">
                <summary class="ai-bell-button">
                    <span class="ai-bell-icon">&#128276;</span>
                    <span>Notifications IA</span>
                    <span class="ai-bell-count" id="ai-bell-count">{{ $unreadAiNotifications }}</span>
                </summary>
                <div class="ai-bell-panel">
                    <div class="ai-bell-head">
                        <strong>Alertes recentes</strong>
                        <span class="muted">Module IA</span>
                    </div>
                    <div class="stack" id="ai-bell-list">
                        @forelse($recentAiNotifications as $notification)
                            <div class="note">
                                <div class="note-head">
                                    <strong>{{ $notification->titre }}</strong>
                                    <span class="status-badge {{ $notification->niveau_gravite === 'eleve' ? 'status-danger' : ($notification->niveau_gravite === 'faible' ? 'status-success' : 'status-warning') }}">{{ $notification->niveau_gravite }}</span>
                                </div>
                                <div class="muted">{{ $notification->message }}</div>
                            </div>
                        @empty
                            <p class="muted">Aucune notification IA pour le moment.</p>
                        @endforelse
                    </div>
                </div>
            </details>
            <div class="actions">
                <button type="button" class="btn btn-secondary" id="ai-run-local-analysis">Analyse locale</button>
                <button type="button" class="btn btn-primary" id="ai-run-analysis">Analyse avec IA</button>
                <button type="button" class="btn btn-secondary" id="ai-refresh-analysis">Rafraichir le dashboard IA</button>
            </div>
        </div>
    </div>

    <div id="ai-loading" class="dashboard-ai-loading" hidden aria-live="polite">
        <span class="dashboard-ai-loading-spinner" aria-hidden="true"></span>
        <div class="dashboard-ai-loading-text">
            <strong id="ai-loading-title">Analyse en cours...</strong>
            <span id="ai-loading-message">Veuillez patienter pendant que le module IA traite les donnees.</span>
        </div>
    </div>
    <div class="dashboard-photo-top">
        <article class="card dashboard-photo-card dashboard-photo-gauge">
            <div class="dashboard-photo-title">Note generale</div>
            <div class="ring-wrap dashboard-photo-ring">
                <canvas id="dashboard-ai-score-ring"></canvas>
                <strong id="dashboard-ai-score-text">{{ $aiKpis['score'] }}/100</strong>
            </div>
            <details class="dashboard-module-toggle dashboard-photo-toggle">
                <summary>
                    <div>
                        <strong>Details de la note</strong>
                        <span class="muted">Ouvrir les chiffres de lecture</span>
                    </div>
                    <span class="status-badge status-info">2</span>
                </summary>
                <div class="dashboard-module-body">
                    <div class="dashboard-photo-table">
                        <div><span>Gain realise</span><strong id="dashboard-ai-gross-margin">{{ number_format($aiKpis['grossMargin'], 2) }} {{ $appSettings->currency }}</strong></div>
                        <div><span>Vente moyenne</span><strong id="dashboard-ai-average-ticket">{{ number_format($aiKpis['averageTicket'], 2) }} {{ $appSettings->currency }}</strong></div>
                    </div>
                </div>
            </details>
        </article>

        <article class="card dashboard-photo-card dashboard-photo-gauge">
            <div class="dashboard-photo-title dashboard-photo-title-orange">Etat du stock</div>
            <div class="ring-wrap dashboard-photo-ring">
                <canvas id="dashboard-ai-stock-ring"></canvas>
                <strong id="dashboard-ai-stock-text">{{ number_format($stockOverview['health_percentage'] ?? $aiKpis['stockHealth'], 0) }}%</strong>
            </div>
            <details class="dashboard-module-toggle dashboard-photo-toggle">
                <summary>
                    <div>
                        <strong>Details du stock</strong>
                        <span class="muted">Ouvrir les chiffres complets du stock</span>
                    </div>
                    <span class="status-badge status-warning" id="dashboard-ai-stock-badge">{{ $stockOverview['total_products'] ?? $productCount }}</span>
                </summary>
                <div class="dashboard-module-body">
                    <div class="dashboard-photo-table">
                        <div><span>Total produits</span><strong id="dashboard-ai-total-products">{{ $stockOverview['total_products'] ?? $productCount }}</strong></div>
                        <div><span>Produits critiques</span><strong id="dashboard-ai-critical-products">{{ $stockOverview['critical_products'] ?? $lowStockCount }}</strong></div>
                        <div><span>Produits a surveiller</span><strong id="dashboard-ai-watch-products">{{ $stockOverview['watch_products'] ?? 0 }}</strong></div>
                        <div><span>Produits stables</span><strong id="dashboard-ai-healthy-products">{{ $stockOverview['healthy_products'] ?? 0 }}</strong></div>
                    </div>
                </div>
            </details>
        </article>

        <article class="card dashboard-photo-card dashboard-photo-gauge">
            <div class="dashboard-photo-title">Utilisateurs</div>
            <div class="ring-wrap dashboard-photo-ring">
                <canvas id="dashboard-ai-user-ring"></canvas>
                <strong id="dashboard-ai-user-text">{{ number_format($aiKpis['activeUserRate'], 0) }}%</strong>
            </div>
            <details class="dashboard-module-toggle dashboard-photo-toggle">
                <summary>
                    <div>
                        <strong>Details utilisateurs</strong>
                        <span class="muted">Ouvrir les indicateurs d activite</span>
                    </div>
                    <span class="status-badge status-success">2</span>
                </summary>
                <div class="dashboard-module-body">
                    <div class="dashboard-photo-table">
                        <div><span>Alertes IA</span><strong id="ai-alert-count-inline">0</strong></div>
                        <div><span>Activite saine</span><strong id="dashboard-ai-meter-user-text">{{ number_format($aiKpis['activeUserRate'], 0) }}%</strong></div>
                    </div>
                </div>
            </details>
        </article>

        <article class="card dashboard-photo-card dashboard-photo-donut">
            <div class="dashboard-photo-title">Repartition du stock</div>
            <div class="dashboard-ai-canvas-wrap dashboard-ai-canvas-wrap-small">
                <canvas id="dashboard-ai-product-share-chart"></canvas>
            </div>
        </article>
    </div>

    <div class="dashboard-photo-bottom">
        <article class="card dashboard-photo-card">
            <div class="dashboard-photo-title dashboard-photo-title-orange">Produits qui rapportent</div>
            <canvas id="dashboard-ai-category-chart"></canvas>
        </article>
        <article class="card dashboard-photo-card">
            <div class="dashboard-photo-title">Indicateur de priorite</div>
            <div class="dashboard-speedometer" id="dashboard-ai-speedometer">
                <div class="dashboard-speedometer-arc"></div>
                <div class="dashboard-speedometer-center">
                    <strong id="dashboard-ai-priority-score">0%</strong>
                    <span id="dashboard-ai-priority-label">Niveau faible</span>
                </div>
                <div class="dashboard-speedometer-needle" id="dashboard-ai-speedometer-needle"></div>
                <div class="dashboard-speedometer-mark mark-left">Faible</div>
                <div class="dashboard-speedometer-mark mark-center">Moyen</div>
                <div class="dashboard-speedometer-mark mark-right">Eleve</div>
            </div>
            <p class="muted dashboard-speedometer-caption" id="dashboard-ai-priority-caption">Le plan d action s affichera ici apres lecture des donnees.</p>
        </article>
    </div>

    <details class="card dashboard-module-toggle dashboard-module-card dashboard-photo-summary-toggle">
        <summary>
            <div>
                <strong>Synthese rapide IA</strong>
                <span class="muted">Ouvrir les chiffres resumes du module IA</span>
            </div>
            <span class="status-badge status-info">4</span>
        </summary>
        <div class="dashboard-module-body">
            <div class="dashboard-photo-strip">
                <div class="dashboard-photo-strip-item">
                    <span>Note generale</span>
                    <strong id="dashboard-ai-meter-score-text">{{ $aiKpis['score'] }}/100</strong>
                </div>
                <div class="dashboard-photo-strip-item">
                    <span>Etat du stock</span>
                    <strong id="dashboard-ai-meter-stock-text">{{ number_format($aiKpis['stockHealth'], 0) }}%</strong>
                </div>
                <div class="dashboard-photo-strip-item">
                    <span>Factures annulees</span>
                    <strong id="dashboard-ai-meter-cancel-text">{{ number_format($aiKpis['cancelRate'], 0) }}%</strong>
                </div>
                <div class="dashboard-photo-strip-item">
                    <span>Produit leader</span>
                    <strong id="dashboard-ai-top-category">{{ $revenueByProductSeed->first()['label'] ?? ($topCategory['label'] ?? 'Aucune categorie') }}</strong>
                </div>
            </div>
        </div>
    </details>

    <div hidden>
        <canvas id="dashboard-ai-cancel-ring"></canvas>
        <span id="dashboard-ai-growth-badge">{{ $monthlyGrowthRate >= 0 ? 'Hausse' : 'Baisse' }}</span>
        <span id="dashboard-ai-growth-caption">Comparaison directe avec le mois precedent.</span>
        <span id="dashboard-ai-stock-alert-caption">Part des produits qui demandent une verification rapide.</span>
        <span id="dashboard-ai-priority-caption">Cette carte change apres chaque analyse.</span>
        <canvas id="dashboard-ai-stock-health-chart"></canvas>
        <span id="dashboard-ai-top-category-value">{{ isset($topCategory['value']) ? number_format((float) $topCategory['value'], 2) . ' ' . $appSettings->currency : '0' }}</span>
        <span id="dashboard-ai-stock-alert-rate">{{ number_format($stockAlertRate, 1) }}%</span>
        <span id="dashboard-ai-priority-text">{{ $lowStockCount > 0 ? 'Verifier les stocks sensibles' : 'Suivre les ventes du jour' }}</span>
        <div class="dashboard-ai-meter-track"><div class="dashboard-ai-meter-fill" id="dashboard-ai-meter-score-fill" style="width: {{ min(100, max(0, $aiKpis['score'])) }}%"></div></div>
        <div class="dashboard-ai-meter-track"><div class="dashboard-ai-meter-fill stock" id="dashboard-ai-meter-stock-fill" style="width: {{ min(100, max(0, $aiKpis['stockHealth'])) }}%"></div></div>
        <div class="dashboard-ai-meter-track"><div class="dashboard-ai-meter-fill user" id="dashboard-ai-meter-user-fill" style="width: {{ min(100, max(0, $aiKpis['activeUserRate'])) }}%"></div></div>
        <div class="dashboard-ai-meter-track"><div class="dashboard-ai-meter-fill warning" id="dashboard-ai-meter-cancel-fill" style="width: {{ min(100, max(0, $aiKpis['cancelRate'])) }}%"></div></div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.aiRoutes = {
        analyze: "{{ route('ai.analyze') }}",
        ask: "{{ route('ai.ask') }}",
        report: "{{ route('ai.report') }}",
    };
    window.dashboardAiSeed = {
        monthlyRevenue: @json($monthlySeries),
        categoryPerformance: @json($categoryPerformance),
        categoryStock: @json($categoryStock),
        score: @json($aiKpis['score']),
        cancelRate: @json($aiKpis['cancelRate']),
        stockHealth: @json($aiKpis['stockHealth']),
        activeUserRate: @json($aiKpis['activeUserRate']),
        grossMargin: @json($aiKpis['grossMargin']),
        averageTicket: @json($aiKpis['averageTicket']),
        lowStockCount: @json($lowStockCount),
        monthlyGrowthRate: @json($monthlyGrowthRate),
        stockAlertRate: @json($stockAlertRate),
        topCategoryLabel: @json($revenueByProductSeed->first()['label'] ?? ($topCategory['label'] ?? 'Aucune categorie')),
        topCategoryValue: @json(isset($revenueByProductSeed->first()['value']) ? round((float) $revenueByProductSeed->first()['value'], 2) : (isset($topCategory['value']) ? round((float) $topCategory['value'], 2) : 0)),
        stockOverview: @json($stockOverview),
        stockDistribution: @json($stockDistribution),
        revenueByProduct: @json($revenueByProductSeed),
        currency: @json($appSettings->currency),
        notifications: @json($dashboardAiNotifications),
    };
</script>

{{-- Cette section regroupe les indicateurs metier principaux du tableau de bord administrateur. --}}
<section class="dashboard-section-shell">
    <div class="dashboard-reading-stack">
        <div class="dashboard-reading-zone">
            <div class="dashboard-premium-grid dashboard-premium-grid-modules">
                <details class="card dashboard-module-toggle dashboard-module-card dashboard-block dashboard-block-table dashboard-block-invoices">
                    <summary>
                        <div>
                            <strong>Dernieres factures</strong>
                            <span class="muted">Afficher les dernieres factures en detail</span>
                        </div>
                        <span class="status-badge status-info">{{ count($recentInvoices) }}</span>
                    </summary>
                    <div class="dashboard-module-body table-responsive">
                        <table>
                            <thead><tr><th>Numero</th><th>Client</th><th>Commercial</th><th>Total</th><th>Date</th></tr></thead>
                            <tbody>
                            @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->client->name }}</td>
                                    <td>{{ $invoice->user->name }}</td>
                                    <td>{{ number_format($invoice->total_ttc, 2) }} {{ $appSettings->currency }}</td>
                                    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">Aucune facture recente.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                <details class="card dashboard-module-toggle dashboard-module-card dashboard-block dashboard-block-list dashboard-block-ranking">
                    <summary>
                        <div>
                            <strong>Produits les plus vendus</strong>
                            <span class="muted">Afficher le detail des meilleurs produits</span>
                        </div>
                        <span class="status-badge status-success">{{ count($topProducts) }}</span>
                    </summary>
                    <div class="dashboard-module-body">
                        @if(($maxProductAmount ?? 0) > 0)
                            <div class="stack">
                                @foreach($topProducts as $product)
                                    <div class="metric-row">
                                        <div>
                                            <strong>{{ $product->description }}</strong>
                                            <div class="muted">{{ (int) $product->total_quantity }} unite(s) vendue(s)</div>
                                        </div>
                                        <div class="metric-progress">
                                            <div class="metric-progress-fill" style="width: {{ max(8, ($product->total_amount / $maxProductAmount) * 100) }}%"></div>
                                        </div>
                                        <strong>{{ number_format($product->total_amount, 2) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="chart-empty-state chart-empty-state-compact">
                                <strong>Aucun produit vendu pour le moment.</strong>
                                <span>Ce classement se remplira automatiquement apres les premieres ventes.</span>
                            </div>
                        @endif
                    </div>
                </details>
                <details class="card dashboard-module-toggle dashboard-module-card">
                    <summary>
                        <div>
                            <strong>Produits en alerte</strong>
                            <span class="muted">Ouvrir le detail du stock critique</span>
                        </div>
                        <span class="status-badge status-danger">{{ $lowStockCount }}</span>
                    </summary>
                    <div class="dashboard-module-body table-responsive">
                        <table>
                            <thead><tr><th>Produit</th><th>Reference</th><th>Stock</th><th>Minimum</th></tr></thead>
                            <tbody>
                            @forelse($lowStockProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->reference }}</td>
                                    <td><span class="status-badge status-danger">{{ $product->stock }}</span></td>
                                    <td>{{ $product->minimum_stock }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Aucun produit en alerte.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>

                <details class="card dashboard-module-toggle dashboard-module-card">
                    <summary>
                        <div>
                            <strong>Performance des utilisateurs</strong>
                            <span class="muted">Ouvrir le classement des ventes</span>
                        </div>
                        <span class="status-badge status-info">{{ count($topUsers) }}</span>
                    </summary>
                    <div class="dashboard-module-body">
                        @if(($maxUserAmount ?? 0) > 0)
                            <div class="stack">
                                @foreach($topUsers as $user)
                                    <div class="metric-row">
                                        <div>
                                            <strong>{{ $user->name }}</strong>
                                            <div class="muted">{{ $user->factures_count }} facture(s)</div>
                                        </div>
                                        <div class="metric-progress">
                                            <div class="metric-progress-fill accent" style="width: {{ max(8, (($user->total_revenue ?? 0) / $maxUserAmount) * 100) }}%"></div>
                                        </div>
                                        <strong>{{ number_format((float) ($user->total_revenue ?? 0), 2) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="chart-empty-state chart-empty-state-compact">
                                <strong>Aucune performance disponible.</strong>
                                <span>Les resultats des utilisateurs apparaitront ici apres les premieres factures.</span>
                            </div>
                        @endif
                    </div>
                </details>

                <details class="card dashboard-module-toggle dashboard-module-card">
                    <summary>
                        <div>
                            <strong>Acces rapides admin</strong>
                            <span class="muted">Ouvrir les actions principales</span>
                        </div>
                        <span class="status-badge status-success">3</span>
                    </summary>
                    <div class="dashboard-module-body stack">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Gestion des utilisateurs</a>
                        <a href="{{ route('ai.settings.edit') }}" class="btn btn-secondary">Parametres IA</a>
                        <a href="{{ route('settings.edit') }}" class="btn btn-primary">Parametres generaux</a>
                    </div>
                </details>

                <details class="card dashboard-module-toggle dashboard-module-card">
                    <summary>
                        <div>
                            <strong>Derniers mouvements</strong>
                            <span class="muted">Ouvrir l historique recent du stock</span>
                        </div>
                        <span class="status-badge status-warning">{{ count($latestMovements) }}</span>
                    </summary>
                    <div class="dashboard-module-body stack">
                        @forelse($latestMovements as $movement)
                            <div class="note">
                                <div class="note-head">
                                    <strong>{{ $movement->produit->name }}</strong>
                                    <span class="status-badge {{ $movement->movement_type === 'entree' ? 'status-success' : ($movement->movement_type === 'sortie' ? 'status-danger' : 'status-warning') }}">{{ ucfirst($movement->movement_type) }}</span>
                                </div>
                                <div>{{ $movement->reason }}</div>
                                <div class="muted">{{ $movement->quantity }} unite(s) • {{ $movement->user->name }} • {{ $movement->movement_date->format('d/m/Y') }}</div>
                            </div>
                        @empty
                            <p class="muted">Aucun mouvement recent.</p>
                        @endforelse
                    </div>
                </details>

                <details class="card dashboard-module-toggle dashboard-module-card">
                    <summary>
                        <div>
                            <strong>Activites recentes</strong>
                            <span class="muted">Ouvrir le journal des actions</span>
                        </div>
                        <span class="status-badge status-info">{{ count($recentActivities) }}</span>
                    </summary>
                    <div class="dashboard-module-body">
                        <div class="stack">
                            @forelse($recentActivities as $activity)
                                <div class="note">
                                    <div class="note-head">
                                        <strong>{{ str_replace('_', ' ', $activity->action) }}</strong>
                                        <span class="status-badge status-info">{{ $activity->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <div class="muted">{{ $activity->user?->name ?: 'Systeme' }}</div>
                                </div>
                            @empty
                                <p class="muted">Aucune activite recente.</p>
                            @endforelse
                        </div>
                        <div class="actions" style="margin-top: 12px;">
                            <a href="{{ route('activities.index') }}" class="btn btn-secondary">Voir tout</a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</section>
@endif
@endsection
