{{-- Cette vue est le layout principal partage par les pages connectees de l application. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $appSettings->company_name ?? 'DEV IA')</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
    <script src="{{ asset('app.js') }}?v={{ filemtime(public_path('app.js')) }}" defer></script>
</head>
<body class="{{ auth()->user()->isAdministrator() ? 'role-admin' : 'role-employee' }}">
<div class="app-shell">
    {{-- Cette colonne affiche la navigation principale et les informations du compte connecte. --}}
    <aside class="sidebar">
        <div>
            <div class="brand">
                <div class="brand-mark">
                    @if($appSettings->logo_path)
                        <img src="{{ asset('storage/' . $appSettings->logo_path) }}?v={{ $appSettings->updated_at?->timestamp ?? time() }}" alt="Logo" class="brand-logo">
                    @else
                        GA
                    @endif
                </div>
                <div>
                    <h1>{{ $appSettings->company_name }}</h1>
                    <p>{{ auth()->user()->isAdministrator() ? 'Administration' : 'Espace employe' }}</p>
                </div>
            </div>

            <div class="role-panel {{ auth()->user()->isAdministrator() ? 'role-panel-admin' : 'role-panel-employee' }}">
                {{-- Ce bloc affiche le niveau d acces et le texte de presentation du role courant. --}}
                <span class="role-panel-label">{{ auth()->user()->isAdministrator() ? 'Acces complet' : 'Acces operationnel' }}</span>
                <strong>{{ auth()->user()->isAdministrator() ? 'Administrateur principal' : 'Employe vente et stock' }}</strong>
                <p>{{ auth()->user()->isAdministrator() ? 'Vous pilotez les parametres, les utilisateurs et la supervision globale.' : 'Vous creez les ventes, consultez le stock disponible et gerez votre profil.' }}</p>
            </div>

            <nav class="nav-links">
                @if(auth()->user()->isAdministrator())
                    {{-- Ces liens correspondent au menu principal visible pour l administrateur. --}}
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">Categories</a>
                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">Produits</a>
                    <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">Clients</a>
                    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.*') ? 'active' : '' }}">Factures</a>
                    <a href="{{ route('stock-movements.index') }}" class="{{ request()->routeIs('stock-movements.*') ? 'active' : '' }}">Mouvements stock</a>
                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">Mon profil</a>
                    <a href="{{ route('settings.edit') }}" class="{{ request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('ai.settings.*') ? 'active' : '' }}">Parametres</a>
                @else
                    {{-- Ces liens correspondent au menu principal visible pour l employe. --}}
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Accueil</a>
                    <a href="{{ route('invoices.create') }}" class="{{ request()->routeIs('invoices.create') ? 'active' : '' }}">Vendre / facturer</a>
                    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') || request()->routeIs('invoices.show') ? 'active' : '' }}">Mes factures</a>
                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">Stock disponible</a>
                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">Mon profil</a>
                @endif
            </nav>
        </div>
        <div class="sidebar-footer">
            <div class="user-badge">
                @if(auth()->user()->profile_photo_path)
                    <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}?v={{ auth()->user()->updated_at?->timestamp ?? time() }}" alt="Profil" class="profile-avatar">
                @else
                    <div class="profile-avatar avatar-fallback">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                @endif
                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <div class="muted">{{ auth()->user()->resolved_role_label }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger btn-block" type="submit">Deconnexion</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        {{-- Cette zone affiche le contenu propre a chaque page. --}}
        <header class="topbar">
            <div>
                <h2>@yield('page-title', 'Dashboard')</h2>
                <p class="muted">@yield('page-description', 'Gestion commerciale et stock')</p>
            </div>
            <div class="topbar-meta">
                {{-- Ces pastilles affichent le role, la devise et la TVA par defaut. --}}
                <span class="pill role-pill {{ auth()->user()->isAdministrator() ? 'role-pill-admin' : 'role-pill-employee' }}">{{ auth()->user()->resolved_role_label }}</span>
                <span class="pill">{{ $appSettings->currency }}</span>
                <span class="pill">TVA: {{ number_format((float) $appSettings->default_tax_rate, 2) }}%</span>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
@if(auth()->user()->isAdministrator())
    @php
        $sidepanelSummaryPayload = session('ai_summary_payload');
        $sidepanelSummaryError = $errors->first('ai_summary');
        $sidepanelShouldOpen = session('ai_sidepanel_open', false);
        $sidepanelOriginLabel = data_get($sidepanelSummaryPayload, 'analysis_origin_label', 'IA');
        $sidepanelOriginText = trim((string) data_get($sidepanelSummaryPayload, 'executive_summary', ''));
        $sidepanelFinanceText = trim((string) data_get($sidepanelSummaryPayload, 'detailed_analysis.finance', ''));
        $sidepanelStockText = trim((string) data_get($sidepanelSummaryPayload, 'detailed_analysis.stock', ''));
        $sidepanelBehaviorText = trim((string) data_get($sidepanelSummaryPayload, 'detailed_analysis.behavior', ''));
        $sidepanelScore = data_get($sidepanelSummaryPayload, 'global_score.score');
    @endphp
    <script>
        window.aiRoutes = window.aiRoutes || {
            analyze: "{{ route('ai.analyze', [], false) }}",
            interpret: "{{ route('ai.interpret', [], false) }}",
            ask: "{{ route('ai.ask', [], false) }}",
            report: "{{ route('ai.report', [], false) }}",
        };
        window.initialAiSummaryPayload = @json($sidepanelSummaryPayload);
    </script>
    <button type="button" class="ai-fab ai-fab-wide" id="ai-fab">🤖 IA</button>
    <aside class="ai-sidepanel" id="ai-sidepanel">
        <div class="ai-sidepanel-head">
            <div>
                <strong>Assistant IA</strong>
                <div class="muted">Calcul local du dashboard, puis resume IA et questions sur les donnees reelles</div>
            </div>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-close">Fermer</button>
        </div>
        <div class="stack ai-sidepanel-actions">
            {{-- Ces boutons commandent les actions principales du panneau IA. --}}
            <button type="button" class="btn btn-primary" id="ai-sidepanel-run" onclick="window.triggerAiAnalysis && window.triggerAiAnalysis(); return false;">Calculer les donnees</button>
            <form method="POST" action="{{ route('ai.interpret.page') }}" class="ai-sidepanel-summary-form">
                @csrf
                <input type="hidden" name="analysis_period" id="ai-sidepanel-summary-period" value="{{ request('analysis_period', 'annual') }}">
                <button
                    type="submit"
                    class="btn btn-secondary"
                    id="ai-sidepanel-summary"
                    onclick="var select=document.getElementById('dashboard-analysis-period'); var hidden=document.getElementById('ai-sidepanel-summary-period'); if(select && hidden){hidden.value=select.value;} document.getElementById('ai-sidepanel')?.classList.add('open'); this.textContent='Resume IA en cours...'; var content=document.getElementById('ai-sidepanel-content'); if(content){content.innerHTML='<div class=&quot;note&quot;><div class=&quot;note-head&quot;><strong>Resume IA en cours</strong><span class=&quot;status-badge status-warning&quot;>En cours</span></div><div class=&quot;muted&quot;>L IA lit les donnees calculees et prepare le resume telechargeable.</div></div>';}"
                >Generer le resume IA</button>
            </form>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-ask-open">Poser une question</button>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-reset">Reinitialiser</button>
            <a href="{{ route('ai.report') }}" class="btn btn-secondary {{ data_get($sidepanelSummaryPayload, 'analysis_origin') === 'ia' ? '' : 'is-disabled' }}" id="ai-sidepanel-download" aria-disabled="{{ data_get($sidepanelSummaryPayload, 'analysis_origin') === 'ia' ? 'false' : 'true' }}">Telecharger le resume</a>
        </div>
        <div class="stack" id="ai-sidepanel-content">
            @if($sidepanelSummaryError)
                <div class="note">
                    <div class="note-head">
                        <strong>Resume IA interrompu</strong>
                        <span class="status-badge status-danger">Erreur</span>
                    </div>
                    <div class="muted">{{ $sidepanelSummaryError }}</div>
                </div>
            @elseif(is_array($sidepanelSummaryPayload) && data_get($sidepanelSummaryPayload, 'analysis_origin') === 'ia')
                <div class="note">
                    <div class="note-head">
                        <strong>Source</strong>
                        <span class="status-badge status-success">{{ $sidepanelOriginLabel }}</span>
                    </div>
                    <div class="muted">Resume IA base sur les donnees calculees du dashboard.</div>
                </div>
                <div class="note">
                    <div class="note-head">
                        <strong>Rapport rapide</strong>
                        <span class="status-badge status-info">{{ $sidepanelScore }}/100</span>
                    </div>
                    <div class="muted">{{ $sidepanelOriginText !== '' ? $sidepanelOriginText : 'Le resume IA est pret.' }}</div>
                </div>
                <div class="note">
                    <strong>Finance</strong>
                    <div class="muted">{{ $sidepanelFinanceText !== '' ? $sidepanelFinanceText : 'Lecture finance disponible dans le rapport.' }}</div>
                </div>
                <div class="note">
                    <strong>Stock</strong>
                    <div class="muted">{{ $sidepanelStockText !== '' ? $sidepanelStockText : 'Lecture stock disponible dans le rapport.' }}</div>
                </div>
                <div class="note">
                    <strong>Utilisateurs</strong>
                    <div class="muted">{{ $sidepanelBehaviorText !== '' ? $sidepanelBehaviorText : 'Lecture utilisateurs disponible dans le rapport.' }}</div>
                </div>
            @else
                <div class="note">
                    <div class="note-head">
                        <strong>Source</strong>
                        <span class="status-badge status-info" id="ai-sidepanel-origin-badge">En attente</span>
                    </div>
                    <div class="muted" id="ai-sidepanel-origin-text">Le panneau indiquera quand l IA aura resume les donnees calculees du dashboard.</div>
                </div>
                <p class="muted">Le resume IA s affichera ici apres le calcul du dashboard.</p>
            @endif
        </div>
    </aside>

    <div class="ai-dialog" id="ai-question-dialog" hidden>
        <div class="ai-dialog-backdrop" data-ai-dialog-close></div>
        <div class="ai-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="ai-dialog-title">
        <div class="ai-dialog-head">
            <div>
                <strong id="ai-dialog-title">Question a l assistant IA</strong>
                <div class="muted">Posez votre question sur les donnees calculees, le resume IA, les ventes, le stock ou les factures, puis l IA vous repondra clairement.</div>
            </div>
            <div class="ai-dialog-toolbar">
                <span class="ai-chat-counter" id="ai-dialog-history-count">0 echange</span>
                <button type="button" class="btn btn-secondary ai-chat-icon-btn" id="ai-dialog-new-conversation" title="Nouvelle conversation" aria-label="Nouvelle conversation">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                </button>
                <button type="button" class="btn btn-secondary" id="ai-dialog-close">Fermer</button>
            </div>
        </div>
            <form id="ai-dialog-form" class="stack ai-dialog-form">
                @csrf
                <label for="ai-dialog-question" class="ai-dialog-label">Votre question</label>
                <textarea id="ai-dialog-question" placeholder="Ecrivez votre question sur le resume IA, les ventes, le stock, les factures ou les alertes."></textarea>
                <div class="actions">
                    <button type="submit" class="btn btn-primary" id="ai-dialog-submit">Envoyer la question</button>
                </div>
            </form>
        <div class="ai-dialog-body" id="ai-dialog-body">
            <div class="ai-chat-empty" id="ai-chat-empty">
                <strong>Assistant IA</strong>
                <div class="muted">Vos echanges avec l assistant s afficheront ici. L historique reste visible sur cet appareil et peut etre vide a tout moment.</div>
            </div>
        </div>
    </div>
    </div>
    <script>
        (() => {
            const summaryButton = document.getElementById('ai-sidepanel-summary');
            const sidepanel = document.getElementById('ai-sidepanel');
            const sidepanelContent = document.getElementById('ai-sidepanel-content');
            const downloadButton = document.getElementById('ai-sidepanel-download');
            const analysisPeriodSelect = document.getElementById('dashboard-analysis-period');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const defaultLabel = summaryButton?.textContent?.trim() || 'Generer le resume IA';

            const renderLoading = () => {
                if (!sidepanelContent) return;

                sidepanelContent.innerHTML = `
                    <div class="note">
                        <div class="note-head">
                            <strong>Resume IA en cours</strong>
                            <span class="status-badge status-warning">En cours</span>
                        </div>
                        <div class="muted">L IA lit les donnees calculees et prepare le resume telechargeable.</div>
                    </div>
                `;
            };

            const renderError = (message) => {
                if (!sidepanelContent) return;

                sidepanelContent.innerHTML = `
                    <div class="note">
                        <div class="note-head">
                            <strong>Resume IA interrompu</strong>
                            <span class="status-badge status-danger">Erreur</span>
                        </div>
                        <div class="muted">${message}</div>
                    </div>
                `;
            };

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[character] ?? character));

            const renderSummary = (payload) => {
                if (!sidepanelContent) return;

                const executiveSummary = (payload?.executive_summary || payload?.download_summary?.summary || '').trim();
                const finance = (payload?.detailed_analysis?.finance || '').trim();
                const stock = (payload?.detailed_analysis?.stock || '').trim();
                const behavior = (payload?.detailed_analysis?.behavior || '').trim();
                const score = payload?.global_score?.score ?? 0;
                const periodLabel = payload?.company?.analysis_period_label || 'Periode courante';
                const title = payload?.download_summary?.title || 'Resume de la periode';
                const recommendations = Array.isArray(payload?.recommendations) ? payload.recommendations.slice(0, 3) : [];
                const alerts = Array.isArray(payload?.alerts) ? payload.alerts.slice(0, 3) : [];

                sidepanelContent.innerHTML = `
                    <div class="note">
                        <div class="note-head">
                            <strong>Source</strong>
                            <span class="status-badge status-success">IA</span>
                        </div>
                        <div class="muted">Resume IA base sur les donnees calculees du dashboard.</div>
                    </div>
                    <div class="note">
                        <div class="note-head">
                            <strong>Rapport rapide</strong>
                            <span class="status-badge status-info">${escapeHtml(periodLabel)}</span>
                        </div>
                        <div class="muted">${escapeHtml(title)}</div>
                        <div class="summary-score"><strong>${score}/100</strong></div>
                    </div>
                    <div class="note">
                        <strong>Lecture de la periode</strong>
                        <div class="muted">${escapeHtml(executiveSummary || 'Le resume IA est pret.')}</div>
                    </div>
                    <div class="note">
                        <strong>Stock</strong>
                        <div class="muted">${escapeHtml(stock || 'Lecture stock disponible dans le rapport.')}</div>
                    </div>
                    <div class="note">
                        <strong>Utilisateurs</strong>
                        <div class="muted">${escapeHtml(behavior || 'Lecture utilisateurs disponible dans le rapport.')}</div>
                    </div>
                    <div class="note">
                        <strong>Finance</strong>
                        <div class="muted">${escapeHtml(finance || 'Lecture finance disponible dans le rapport.')}</div>
                    </div>
                    ${alerts.map((alert) => `
                        <div class="note">
                            <div class="note-head">
                                <strong>${escapeHtml(alert?.title || 'Alerte')}</strong>
                                <span class="status-badge ${alert?.severity === 'eleve' ? 'status-danger' : (alert?.severity === 'faible' ? 'status-success' : 'status-warning')}">${escapeHtml(alert?.severity || 'moyen')}</span>
                            </div>
                            <div class="muted">${escapeHtml(alert?.message || '')}</div>
                        </div>
                    `).join('')}
                    <div class="note">
                        <strong>Recommandations</strong>
                        <ul class="ai-summary-list">
                            ${recommendations.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}
                        </ul>
                    </div>
                `;
            };

            @if($sidepanelShouldOpen)
                sidepanel?.classList.add('open');
            @endif
        })();
    </script>
@endif
</body>
</html>
