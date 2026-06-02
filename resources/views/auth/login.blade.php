{{-- Cette vue affiche la page de connexion avec creation de compte reservee a l administrateur. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appSettings->company_name ?? 'DEV IA' }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
</head>
<body class="auth-page">
@php($companyName = $appSettings->company_name ?? 'DEV IA')
<div class="auth-shell auth-shell-compact">
    <div class="auth-card auth-card-compact">
        <aside class="auth-brand-panel auth-brand-panel-compact">
            <div class="auth-brand-wave auth-brand-wave-one"></div>
            <div class="auth-brand-wave auth-brand-wave-two"></div>
            <div class="auth-brand-content">
                <div class="auth-brand-logo">{{ strtoupper(substr($companyName, 0, 2)) }}</div>
                <p class="auth-brand-eyebrow">Bienvenue</p>
                <h1>{{ $companyName }}</h1>
            </div>
        </aside>

        <section class="auth-login-panel auth-login-panel-compact">
            <div class="auth-login-head">
                <p>Connexion et acces utilisateur</p>
                <h2>{{ $companyName }}</h2>
            </div>

            <div class="auth-register-note" style="margin-bottom: 14px;">
                La creation de comptes employe est reservee a l administrateur depuis l interface d administration.
            </div>

            @if(session('success'))
                <div class="alert alert-success auth-alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger auth-alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="auth-login-box">
                <form method="POST" action="{{ route('login.perform') }}" class="auth-form-grid auth-form-grid-compact">
                    @csrf
                    <div>
                        <label for="login">Email et code de connexion</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" placeholder="Entrez votre email ou votre code" required>
                    </div>
                    <div>
                        <label for="password">Mot de passe</label>
                        <input id="password" type="password" name="password" placeholder="Entrez votre mot de passe" required>
                    </div>
                    <label class="auth-remember">
                        <input type="checkbox" name="remember">
                        <span>Se souvenir de moi</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="auth-forgot-link">Mot de passe oublie ?</a>
                    <button class="btn auth-submit-btn" type="submit">Login</button>
                </form>
            </div>
        </section>
    </div>
</div>
</body>
</html>
