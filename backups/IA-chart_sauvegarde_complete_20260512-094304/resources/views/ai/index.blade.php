@extends('layouts.app')
{{-- Cette vue affiche le module IA complet avec l analyse et le chat. --}}

@section('page-title', 'Audit IA')
@section('page-description', 'Analyse simple des ventes, du stock et de l activite')
@section('content')
<section class="hero-banner hero-admin">
    <div>
        <span class="eyebrow">Module IA</span>
        <h3>Analyse claire en quelques secondes</h3>
        <p>Lancez une analyse, regardez les graphiques, posez vos questions et telechargez un rapport PDF facile a lire.</p>
    </div>
    <div class="hero-metrics">
        <div><span>Modele Gemini</span><strong>{{ $settings->openrouter_model }}</strong></div>
        <div><span>Cache</span><strong>{{ $settings->cache_duration_minutes }} min</strong></div>
        <div><span>Sensibilite</span><strong>{{ ucfirst($settings->sensitivity_level) }}</strong></div>
    </div>
</section>

<div class="card stack">
    <div class="toolbar">
        <div>
            <strong>Espace analyse IA</strong>
            <div class="muted">Acces administrateur uniquement. L IA lit les donnees mais ne change rien.</div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" id="ai-run-analysis">Lancer l analyse</button>
            <button type="button" class="btn btn-secondary" id="ai-refresh-analysis">Rafraichir</button>
            <a href="{{ route('ai.report') }}" class="btn btn-secondary">Telecharger rapport IA</a>
        </div>
    </div>

    <div id="ai-loading" class="muted" hidden>Analyse en cours...</div>

    <div id="ai-output" hidden>
        <div class="stats-grid ai-stats-grid">
            <article class="card stat-card"><span>Note generale</span><strong id="ai-score-value">0/100</strong></article>
            <article class="card stat-card"><span>Alertes</span><strong id="ai-alert-count">0</strong></article>
            <article class="card stat-card"><span>Conseils</span><strong id="ai-reco-count">0</strong></article>
            <article class="card stat-card"><span>Points inhabituels</span><strong id="ai-anomaly-count">0</strong></article>
        </div>

        <div class="content-grid ai-dashboard-grid">
            <div class="card">
                <div class="card-head"><h3>Resume executif</h3></div>
                <p id="ai-executive-summary" class="muted"></p>
            </div>
            <div class="card">
                <div class="card-head"><h3>Alertes IA</h3></div>
                <div class="stack" id="ai-alerts-list">
                    @forelse($notifications as $notification)
                        <div class="note">
                            <div class="note-head">
                                <strong>{{ $notification->title }}</strong>
                                <span class="status-badge status-{{ $notification->severity === 'eleve' ? 'danger' : ($notification->severity === 'faible' ? 'success' : 'warning') }}">{{ $notification->severity }}</span>
                            </div>
                            <div class="muted">{{ $notification->message }}</div>
                        </div>
                    @empty
                        <p class="muted">Aucune notification IA.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="ai-chart-grid">
            <div class="card"><div class="card-head"><h3>Evolution des ventes</h3></div><canvas id="ai-chart-revenue"></canvas></div>
            <div class="card"><div class="card-head"><h3>Gain realise</h3></div><canvas id="ai-chart-margin"></canvas></div>
            <div class="card"><div class="card-head"><h3>Ventes par produit</h3></div><canvas id="ai-chart-products"></canvas></div>
            <div class="card"><div class="card-head"><h3>Mouvement du stock</h3></div><canvas id="ai-chart-stock"></canvas></div>
            <div class="card"><div class="card-head"><h3>Activite utilisateurs</h3></div><canvas id="ai-chart-users"></canvas></div>
            <div class="card"><div class="card-head"><h3>Note generale</h3></div><canvas id="ai-chart-score"></canvas></div>
        </div>

        <div class="content-grid ai-dashboard-grid">
            <div class="card">
                <div class="card-head"><h3>Explication detaillee</h3></div>
                <div class="stack">
                    <div class="note"><strong>Ventes</strong><div class="muted" id="ai-analysis-finance"></div></div>
                    <div class="note"><strong>Stock</strong><div class="muted" id="ai-analysis-stock"></div></div>
                    <div class="note"><strong>Utilisateurs</strong><div class="muted" id="ai-analysis-behavior"></div></div>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h3>Conseils</h3></div>
                <div class="stack" id="ai-recommendations"></div>
            </div>
        </div>

        <div class="content-grid ai-dashboard-grid">
            <div class="card full">
                <div class="card-head"><h3>Rapport pour la direction</h3></div>
                <div class="stack">
                    <strong id="ai-report-title"></strong>
                    <p id="ai-report-summary" class="muted"></p>
                    <div class="note">
                        <strong>Decision conseillee</strong>
                        <div id="ai-report-conclusion" class="muted"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid ai-dashboard-grid">
            <div class="card">
                <div class="card-head"><h3>Points inhabituels</h3></div>
                <div class="stack" id="ai-anomalies"></div>
            </div>
            <div class="card">
                <div class="card-head"><h3>Poser une question</h3></div>
                <form id="ai-chat-form" class="stack">
                    @csrf
                    <textarea id="ai-chat-question" placeholder="Posez une question si un resultat n est pas clair."></textarea>
                    <button type="submit" class="btn btn-primary">Poser la question</button>
                </form>
                <div class="stack" id="ai-chat-history"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.aiRoutes = {
        analyze: "{{ route('ai.analyze') }}",
        ask: "{{ route('ai.ask') }}",
        report: "{{ route('ai.report') }}",
    };
</script>
@endsection
