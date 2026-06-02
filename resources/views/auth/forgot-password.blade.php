{{-- Cette vue permet de demander un lien de reinitialisation du mot de passe. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublie - {{ $appSettings->company_name ?? 'DEV IA' }}</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
    @endif
</head>
<body class="auth-page">
@php($companyName = $appSettings->company_name ?? 'DEV IA')
<div class="auth-shell">
    <div class="auth-card">
        <aside class="auth-brand-panel">
            <div class="auth-brand-wave auth-brand-wave-one"></div>
            <div class="auth-brand-wave auth-brand-wave-two"></div>
            <div class="auth-brand-content">
                <div class="auth-brand-logo">{{ strtoupper(substr($companyName, 0, 2)) }}</div>
                <p class="auth-brand-eyebrow">Recuperation</p>
                <h1>{{ $companyName }}</h1>
            </div>
        </aside>

        <section class="auth-login-panel">
            <div class="auth-login-head">
                <p>Securite du compte</p>
                <h2>Mot de passe oublie</h2>
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
                <form method="POST" action="{{ route('password.email') }}" class="auth-form-grid">
                    @csrf
                    <div class="auth-register-note">
                        Entrez l adresse email de votre compte. Si elle existe, un lien de reinitialisation sera envoye.
                    </div>
                    <div>
                        <label for="email">Adresse email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Votre email" required>
                    </div>
                    <button class="btn auth-submit-btn" type="submit">Envoyer le lien</button>
                    <a href="{{ route('login') }}" class="auth-forgot-link">Retour a la connexion</a>
                </form>
            </div>
        </section>
    </div>
</div>
</body>
</html>
