{{-- Cette vue genere un rapport PDF IA avec une presentation sobre, propre et proche d un document Word. --}}
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
        .logo-cell {
            width: 110px;
        }
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
        .meta-line strong {
            color: #142033;
        }
        .section {
            margin-top: 16px;
        }
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
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .detail-table td {
            vertical-align: top;
            width: 33.33%;
            padding-right: 14px;
        }
        .subheading {
            font-weight: bold;
            color: #142033;
            margin-bottom: 4px;
        }
        .plain-list {
            margin: 6px 0 0 18px;
            padding: 0;
        }
        .plain-list li {
            margin-bottom: 7px;
        }
        .label-strong {
            font-weight: bold;
            color: #142033;
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
        $alerts = data_get($analysis, 'alerts', []);
        $anomalies = data_get($analysis, 'anomalies', []);
        $recommendations = data_get($analysis, 'recommendations', []);
        $breakdown = data_get($analysis, 'global_score.breakdown', []);
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
                    <div class="document-eyebrow">Rapport d analyse intelligente</div>
                    <div class="document-title">{{ data_get($analysis, 'download_summary.title') }}</div>
                    <div class="document-subtitle">{{ $company->company_name }}</div>
                </td>
                <td class="header-meta">
                    <div class="meta-line"><strong>Date :</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
                    <div class="meta-line"><strong>Source :</strong> Analyse IA verifiee</div>
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
            <tr>
                <td class="info-label">Lecture rapide</td>
                <td>
                    @if($score >= 70)
                        Situation globalement solide
                    @elseif($score >= 50)
                        Situation a surveiller
                    @else
                        Situation critique
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Resume executif</span></div>
        <p class="paragraph">{{ data_get($analysis, 'executive_summary') }}</p>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Synthese pour la direction</span></div>
        <p class="paragraph">{{ data_get($analysis, 'download_summary.summary') }}</p>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Lecture detaillee</span></div>
        <table class="detail-table">
            <tr>
                <td>
                    <div class="subheading">Ventes</div>
                    <p class="paragraph">{{ data_get($analysis, 'detailed_analysis.finance') }}</p>
                </td>
                <td>
                    <div class="subheading">Stock</div>
                    <p class="paragraph">{{ data_get($analysis, 'detailed_analysis.stock') }}</p>
                </td>
                <td>
                    <div class="subheading">Utilisateurs</div>
                    <p class="paragraph">{{ data_get($analysis, 'detailed_analysis.behavior') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Anomalies detectees</span></div>
        @if(count($anomalies) > 0)
            <ul class="plain-list">
                @foreach($anomalies as $anomaly)
                    <li>
                        <span class="label-strong">{{ $anomaly['title'] ?? 'Anomalie' }}</span>
                        @if(!empty($anomaly['severity']))
                            <span>({{ $anomaly['severity'] }})</span>
                        @endif
                        : {{ $anomaly['message'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        @else
            <p class="paragraph">Aucune anomalie majeure n a ete relevee dans cette analyse.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Alertes importantes</span></div>
        @if(count($alerts) > 0)
            <ul class="plain-list">
                @foreach($alerts as $alert)
                    <li>
                        <span class="label-strong">{{ $alert['title'] ?? 'Alerte' }}</span>
                        @if(!empty($alert['severity']))
                            <span>({{ $alert['severity'] }})</span>
                        @endif
                        : {{ $alert['message'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        @else
            <p class="paragraph">Aucune alerte importante n a ete retournee pour le moment.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Lecture de la note globale</span></div>
        <p class="paragraph"><span class="label-strong">Explication generale :</span> {{ data_get($analysis, 'global_score.explanation') }}</p>
        @if(count($breakdown) > 0)
            <ul class="plain-list">
                @foreach($breakdown as $row)
                    <li>
                        <span class="label-strong">{{ $row['label'] ?? 'Critere' }}</span> :
                        {{ $row['points'] ?? 0 }} point(s).
                        {{ $row['justification'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Recommandations</span></div>
        @if(count($recommendations) > 0)
            <ul class="plain-list">
                @foreach($recommendations as $recommendation)
                    <li>{{ $recommendation }}</li>
                @endforeach
            </ul>
        @else
            <p class="paragraph">Aucune recommandation detaillee n a ete retournee.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title"><span class="lead">Conclusion generale</span></div>
        <p class="paragraph">{{ data_get($analysis, 'download_summary.conclusion') }}</p>
    </div>

    <div class="footer-note">
        Rapport genere automatiquement depuis DEV IA a partir d une analyse Gemini verifiee.
    </div>
</body>
</html>
