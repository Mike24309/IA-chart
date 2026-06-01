{{-- Cette vue est le layout principal partage par les pages connectees de l application. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DEV IA')</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
        <script src="{{ asset('app.js') }}?v={{ filemtime(public_path('app.js')) }}" defer></script>
    @endif
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
                {{-- Ce bouton change le theme global de l interface. --}}
                <button type="button" class="btn btn-secondary theme-toggle" data-theme-toggle>Mode sombre</button>
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
    <script>
        window.aiRoutes = window.aiRoutes || {
            analyze: "{{ route('ai.analyze') }}",
            ask: "{{ route('ai.ask') }}",
            report: "{{ route('ai.report') }}",
        };
    </script>
    <button type="button" class="ai-fab ai-fab-wide" id="ai-fab">🤖 IA</button>
    <aside class="ai-sidepanel" id="ai-sidepanel">
        <div class="ai-sidepanel-head">
            <div>
                <strong>Assistant IA</strong>
                <div class="muted">Audit et explication rapide</div>
            </div>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-close">Fermer</button>
        </div>
        <div class="stack">
            {{-- Ces boutons commandent les actions principales du panneau IA. --}}
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-run-local">Analyse locale</button>
            <button type="button" class="btn btn-primary" id="ai-sidepanel-run">Analyse avec IA</button>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-summary">Faire un resume</button>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-ask-open">Poser une question</button>
            <button type="button" class="btn btn-secondary" id="ai-sidepanel-reset">Reinitialiser</button>
            <a href="{{ route('ai.report') }}" class="btn btn-secondary is-disabled" id="ai-sidepanel-download" aria-disabled="true">Telecharger le resume</a>
        </div>
        <div class="stack" id="ai-sidepanel-content">
            <div class="note">
                <div class="note-head">
                    <strong>Source</strong>
                    <span class="status-badge status-info" id="ai-sidepanel-origin-badge">En attente</span>
                </div>
                <div class="muted" id="ai-sidepanel-origin-text">Le panneau indiquera si le resultat vient de l IA ou d une analyse locale.</div>
            </div>
            <p class="muted">Le resume IA s affichera ici apres analyse.</p>
        </div>
    </aside>

    <div class="ai-dialog" id="ai-question-dialog" hidden>
        <div class="ai-dialog-backdrop" data-ai-dialog-close></div>
        <div class="ai-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="ai-dialog-title">
            <div class="ai-dialog-head">
                <div>
                    <strong id="ai-dialog-title">Question a l assistant IA</strong>
                    <div class="muted">Posez votre question dans une vraie boite de dialogue claire et professionnelle.</div>
                </div>
                <button type="button" class="btn btn-secondary" id="ai-dialog-close">Fermer</button>
            </div>
            <form id="ai-dialog-form" class="stack ai-dialog-form">
                @csrf
                <label for="ai-dialog-question" class="ai-dialog-label">Votre question</label>
                <textarea id="ai-dialog-question" placeholder="Ecrivez votre question sur l analyse, les ventes, le stock, les factures ou les alertes."></textarea>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Envoyer la question</button>
                </div>
            </form>
            <div class="ai-dialog-body" id="ai-dialog-body">
                <div class="ai-chat-empty" id="ai-chat-empty">
                    <strong>Assistant IA</strong>
                    <div class="muted">Vos echanges avec l assistant s afficheront ici, dans une presentation plus propre et lisible.</div>
                </div>
            </div>
        </div>
    </div>
@endif
</body>
</html>
