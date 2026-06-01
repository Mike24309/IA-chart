{{-- Cette vue permet de definir un nouveau mot de passe apres reception du lien email. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - DEV IA</title>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
    @endif
</head>
<body class="auth-page">
<div class="auth-shell">
    <div class="auth-card">
        <aside class="auth-brand-panel">
            <div class="auth-brand-wave auth-brand-wave-one"></div>
            <div class="auth-brand-wave auth-brand-wave-two"></div>
            <div class="auth-brand-content">
                <div class="auth-brand-logo">DI</div>
                <p class="auth-brand-eyebrow">Securite</p>
                <h1>DEV IA</h1>
            </div>
        </aside>

        <section class="auth-login-panel">
            <div class="auth-login-head">
                <p>Reinitialisation</p>
                <h2>Nouveau mot de passe</h2>
            </div>

            @if($errors->any())
                <div class="alert alert-danger auth-alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="auth-login-box">
                <form method="POST" action="{{ route('password.update') }}" class="auth-form-grid">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div>
                        <label for="email">Adresse email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
                    </div>
                    <div>
                        <label for="password">Nouveau mot de passe</label>
                        <input id="password" type="password" name="password" placeholder="Minimum 6 caracteres" required>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirmation</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Retapez le mot de passe" required>
                    </div>
                    <button class="btn auth-submit-btn" type="submit">Enregistrer le nouveau mot de passe</button>
                    <a href="{{ route('login') }}" class="auth-forgot-link">Retour a la connexion</a>
                </form>
            </div>
        </section>
    </div>
</div>
</body>
</html>
