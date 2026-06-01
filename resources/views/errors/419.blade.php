<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session expiree</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-card">
            <aside class="auth-brand-panel">
                <div class="auth-brand-wave auth-brand-wave-one"></div>
                <div class="auth-brand-wave auth-brand-wave-two"></div>
                <div class="auth-brand-content">
                    <div class="auth-brand-logo">DI</div>
                    <p class="auth-brand-eyebrow">Session expiree</p>
                    <h1>DEV IA</h1>
                </div>
            </aside>
            <section class="auth-login-panel">
                <div class="auth-login-head">
                    <p>Protection de session</p>
                    <h2>Page expiree</h2>
                </div>
                <div class="auth-login-box stack">
                    <div class="note">
                        <strong>La page a expire.</strong>
                        <div class="muted">Rafraichissez la page ou retournez a la connexion, puis recommencez votre action.</div>
                    </div>
                    <div class="actions">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Retour</a>
                        <a href="{{ route('login') }}" class="btn btn-primary">Reconnecter</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
