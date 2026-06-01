{{-- Cette vue genere un PDF centré sur le resume IA telechargeable. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 25px 30px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11.5px;
            line-height: 1.7;
            color: #1a2433;
            margin: 0;
        }
        h1, h2, h3, p { margin: 0; }
        .document-header {
            border-bottom: 2px solid #203a63;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-cell { width: 110px; }
        .logo-box {
            width: 90px;
            height: 90px;
            text-align: center;
            vertical-align: middle;
        }
        .logo-box img {
            width: auto;
            height: auto;
            max-width: 90px;
            max-height: 90px;
            display: block;
            margin: 0 auto;
        }
        .logo-fallback {
            width: 90px;
            height: 90px;
            line-height: 90px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #203a63;
            border: 1px solid #d7dfeb;
        }
        .document-eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #627188;
            margin-bottom: 6px;
        }
        .document-title {
            font-size: 23px;
            font-weight: bold;
            color: #142033;
            margin-bottom: 4px;
        }
        .document-subtitle {
            font-size: 11px;
            color: #4b5a70;
        }
        .header-meta {
            width: 240px;
            text-align: right;
        }
        .meta-line {
            margin-bottom: 6px;
            color: #415066;
        }
        .meta-line strong { color: #142033; }
        .section { margin-top: 16px; }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #142033;
            margin-bottom: 8px;
        }
        .section-title .lead {
            display: inline-block;
            padding-bottom: 3px;
            border-bottom: 1px solid #c9d4e2;
        }
        .summary-box {
            background: #f5f8fc;
            border: 1px solid #d8e1ee;
            border-left: 4px solid #203a63;
            padding: 12px 14px;
            border-radius: 6px;
            margin-top: 6px;
        }
        .summary-box-soft {
            background: #fbfcfe;
            border-left-color: #f06b26;
        }
        .paragraph {
            margin-bottom: 10px;
            text-align: justify;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px 0;
        }
        .info-label {
            width: 150px;
            font-weight: bold;
            color: #203a63;
        }
        .badge-row {
            display: block;
            margin-top: 10px;
            font-size: 11px;
            color: #4b5a70;
        }
        .footer-note {
            margin-top: 22px;
            padding-top: 10px;
            border-top: 1px solid #d7dfeb;
            font-size: 10px;
            color: #6b778a;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $score = (int) data_get($analysis, 'global_score.score', 0);
        $periodLabel = data_get($analysis, 'metrics.company.analysis_period_label')
            ?? data_get($analysis, 'company.analysis_period_label')
            ?? 'Periode courante';
        $summaryText = data_get($analysis, 'download_summary.summary');
        $conclusionText = data_get($analysis, 'download_summary.conclusion');
        $financeText = data_get($analysis, 'detailed_analysis.finance');
        $stockText = data_get($analysis, 'detailed_analysis.stock');
        $behaviorText = data_get($analysis, 'detailed_analysis.behavior');
        $alerts = collect(data_get($analysis, 'alerts', []));
        $anomalies = collect(data_get($analysis, 'anomalies', []));
        $recommendations = collect(data_get($analysis, 'recommendations', []));
        $scoreBreakdown = collect(data_get($analysis, 'global_score.breakdown', []));
        $stockOverview = data_get($analysis, 'stock_overview', []);
        $revenueTrend = collect(data_get($analysis, 'charts.revenue_trend', []));
        $grossMargin = collect(data_get($analysis, 'charts.gross_margin', []));
        $revenueByProduct = collect(data_get($analysis, 'charts.revenue_by_product', []));
        $stockDistribution = collect(data_get($analysis, 'charts.stock_distribution', []));
        $stockRotation = collect(data_get($analysis, 'charts.stock_rotation', []));
        $userActivity = collect(data_get($analysis, 'charts.user_activity', []));

        $firstRevenue = (float) data_get($revenueTrend->first(), 'value', 0);
        $lastRevenue = (float) data_get($revenueTrend->last(), 'value', 0);
        $growth = $firstRevenue > 0 ? round((($lastRevenue - $firstRevenue) / $firstRevenue) * 100, 1) : null;
        $topProducts = $revenueByProduct->sortByDesc('value')->take(3)->values();
        $criticalStocks = $stockDistribution->filter(fn ($row) => in_array(data_get($row, 'status'), ['critical', 'watch'], true))->take(4)->values();
        $userActivityTop = $userActivity->sortByDesc('value')->take(3)->values();
    @endphp

    <div class="document-header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <div class="logo-box">
                        @if(!empty($companyLogoPath))
                            <img src="{{ $companyLogoPath }}" alt="Logo entreprise">
                        @else
                            <div class="logo-fallback">{{ strtoupper(substr($company->company_name ?? 'DI', 0, 2)) }}</div>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="document-eyebrow">Resume IA telechargeable</div>
                    <div class="document-title">{{ data_get($analysis, 'download_summary.title') }}</div>
                    <div class="document-subtitle">{{ $company->company_name }}</div>
                </td>
                <td class="header-meta">
                    <div class="meta-line"><strong>Date :</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
                    <div class="meta-line"><strong>Source :</strong> {{ data_get($analysis, 'analysis_origin_label', 'Resume IA de l analyse locale') }}</div>
                    <div class="meta-line"><strong>Periode :</strong> {{ $periodLabel }}</div>
                    <div class="meta-line"><strong>Note generale :</strong> {{ $score }}/100</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Informations generales</span></div>
        <table class="info-table">
            <tr>
                <td class="info-label">Entreprise</td>
                <td>{{ $company->company_name }}</td>
            </tr>
            @if(!empty($company->company_address))
                <tr>
                    <td class="info-label">Adresse</td>
                    <td>{{ $company->company_address }}</td>
                </tr>
            @endif
            @if(!empty($company->company_email))
                <tr>
                    <td class="info-label">Email</td>
                    <td>{{ $company->company_email }}</td>
                </tr>
            @endif
            @if(!empty($company->company_phone))
                <tr>
                    <td class="info-label">Telephone</td>
                    <td>{{ $company->company_phone }}</td>
                </tr>
            @endif
        </table>
        <span class="badge-row">
            Lecture rapide :
            @if($score >= 70)
                Situation globalement solide
            @elseif($score >= 50)
                Situation a surveiller
            @else
                Situation critique
            @endif
        </span>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Resume telechargeable</span></div>
        <div class="summary-box">
            <p class="paragraph">{{ $summaryText }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Points essentiels pour la direction</span></div>
        <div class="summary-box summary-box-soft">
            <p class="paragraph">
                Note globale de {{ $score }}/100.
                @if($growth !== null)
                    Evolution du chiffre d affaires sur la periode : {{ $growth > 0 ? '+' : '' }}{{ $growth }}%.
                @endif
                @if(isset($stockOverview['critical_products']))
                    Stock: {{ (int) ($stockOverview['critical_products'] ?? 0) }} produit(s) critique(s), {{ (int) ($stockOverview['watch_products'] ?? 0) }} a surveiller et {{ (int) ($stockOverview['healthy_products'] ?? 0) }} stables.
                @endif
                @if($alerts->count() > 0)
                    Le rapport remonte {{ $alerts->count() }} alerte(s) importante(s) a traiter en priorite.
                @endif
            </p>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Lecture des graphiques</span></div>
        <div class="summary-box">
            <p class="paragraph">
                Les graphiques montrent en priorite l evolution du chiffre d affaires, la repartition des ventes par produit et la situation du stock.
                @if($revenueTrend->count() > 1)
                    La courbe de revenus compare le debut et la fin de la periode pour voir si l activite progresse ou ralentit.
                @endif
                @if($grossMargin->count() > 0)
                    La marge brute permet de verifier si les ventes generent une rentabilite suffisante apres les couts d achat.
                @endif
                @if($stockDistribution->count() > 0)
                    Le graphique de stock indique les produits critiques et ceux a surveiller pour prevenir les ruptures.
                @endif
            </p>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Analyse detaillee</span></div>
        <div class="summary-box summary-box-soft">
            <p class="paragraph">{{ $conclusionText }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Lecture finance</span></div>
        <div class="summary-box">
            <p class="paragraph">{{ $financeText }}</p>
            @if($topProducts->count() > 0)
                <p class="paragraph">
                    Produits qui ressortent le plus:
                    @foreach($topProducts as $product)
                        {{ $product['label'] ?? 'Produit' }} ({{ number_format((float) ($product['value'] ?? 0), 2) }})@if(! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Lecture stock</span></div>
        <div class="summary-box">
            <p class="paragraph">{{ $stockText }}</p>
            @if($criticalStocks->count() > 0)
                <p class="paragraph">
                    Produits sensibles a suivre:
                    @foreach($criticalStocks as $row)
                        {{ $row['label'] ?? 'Produit' }} ({{ $row['status'] ?? 'watch' }})@if(! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Lecture utilisateurs</span></div>
        <div class="summary-box">
            <p class="paragraph">{{ $behaviorText }}</p>
            @if($userActivityTop->count() > 0)
                <p class="paragraph">
                    Utilisateurs les plus actifs:
                    @foreach($userActivityTop as $row)
                        {{ $row['label'] ?? 'Utilisateur' }} ({{ number_format((float) ($row['value'] ?? 0), 0) }})@if(! $loop->last), @endif
                    @endforeach
                </p>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Alerte et vigilance</span></div>
        <div class="summary-box">
            @if($alerts->count() > 0)
                <p class="paragraph">Alertes importantes:</p>
                <ul class="badge-row" style="margin-left: 18px;">
                    @foreach($alerts as $alert)
                        <li>{{ $alert['title'] ?? 'Alerte' }}: {{ $alert['message'] ?? '' }}</li>
                    @endforeach
                </ul>
            @else
                <p class="paragraph">Aucune alerte majeure n a ete remontee pour cette periode.</p>
            @endif

            @if($anomalies->count() > 0)
                <p class="paragraph">Anomalies detectees:</p>
                <ul class="badge-row" style="margin-left: 18px;">
                    @foreach($anomalies as $anomaly)
                        <li>{{ $anomaly['title'] ?? 'Anomalie' }}: {{ $anomaly['message'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Note globale et lecture</span></div>
        <div class="summary-box summary-box-soft">
            <p class="paragraph">{{ data_get($analysis, 'global_score.explanation') }}</p>
            @if($scoreBreakdown->count() > 0)
                <p class="paragraph">Detail du score:</p>
                <ul class="badge-row" style="margin-left: 18px;">
                    @foreach($scoreBreakdown as $row)
                        <li>{{ $row['label'] ?? 'Critere' }}: {{ $row['points'] ?? 0 }} point(s) - {{ $row['justification'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Recommandations prioritaires</span></div>
        <div class="summary-box">
            @if($recommendations->count() > 0)
                <ul class="badge-row" style="margin-left: 18px;">
                    @foreach($recommendations as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            @else
                <p class="paragraph">Aucune recommandation detaillee n a ete retournee.</p>
            @endif
        </div>
    </div>

    <div class="footer-note">
        Rapport genere automatiquement depuis DEV IA a partir du dernier resume IA disponible.
    </div>
</body>
</html>
