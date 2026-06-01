{{-- Cette vue affiche la page de connexion et la creation du compte si besoin. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEV IA</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
</head>
<body class="auth-page">
<div class="auth-shell auth-shell-compact">
    <div class="auth-card auth-card-compact">
        <aside class="auth-brand-panel auth-brand-panel-compact">
            <div class="auth-brand-wave auth-brand-wave-one"></div>
            <div class="auth-brand-wave auth-brand-wave-two"></div>
            <div class="auth-brand-content">
                <div class="auth-brand-logo">DI</div>
                <p class="auth-brand-eyebrow">Bienvenue</p>
                <h1>DEV IA</h1>
            </div>
        </aside>

        <section class="auth-login-panel auth-login-panel-compact">
            <div class="auth-login-head">
                <p>Connexion et acces utilisateur</p>
                <h2>DEV IA</h2>
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
                <div class="auth-panel active" id="login-panel">
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

                        @if($canSelfRegister)
                            <button type="button" class="auth-link-button auth-link-inline" data-auth-target="register-panel">Créer un compte</button>
                        @endif
                    </form>
                </div>

                @if($canSelfRegister)
                    <div class="auth-panel" id="register-panel">
                        <form method="POST" action="{{ route('register.perform') }}" class="auth-form-grid auth-form-grid-compact">
                            @csrf
                            <div class="auth-register-note">
                                Si le compte n existe pas encore, tu peux en creer un maintenant avec ton nom, ton email et un mot de passe.
                            </div>
                            <div>
                                <label for="register_name">Nom complet</label>
                                <input id="register_name" type="text" name="name" value="{{ old('name') }}" placeholder="Votre nom complet" required>
                            </div>
                            <div>
                                <label for="register_email">Adresse email</label>
                                <input id="register_email" type="email" name="email" value="{{ old('email') }}" placeholder="Votre adresse email" required>
                            </div>
                            <div>
                                <label for="register_password">Mot de passe</label>
                                <input id="register_password" type="password" name="password" placeholder="Choisissez un mot de passe" required>
                            </div>
                            <div>
                                <label for="register_password_confirmation">Confirmer le mot de passe</label>
                                <input id="register_password_confirmation" type="password" name="password_confirmation" placeholder="Repetez le mot de passe" required>
                            </div>
                            <button class="btn auth-submit-btn" type="submit">Creer le compte</button>
                            <button type="button" class="auth-link-button auth-link-inline" data-auth-target="login-panel">J ai deja un compte</button>
                        </form>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<script>
    document.querySelectorAll('[data-auth-target]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.auth-panel').forEach((panel) => {
                panel.classList.remove('active');
            });

            const target = document.getElementById(button.getAttribute('data-auth-target'));
            if (target) {
                target.classList.add('active');
            }
        });
    });
</script>
</body>
</html>
