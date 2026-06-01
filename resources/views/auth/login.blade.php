{{-- Cette vue affiche la page de connexion et la creation du premier compte administrateur. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    {{-- Cette partie definit les informations generales de la page de connexion. --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEV IA</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ filemtime(public_path('app.css')) }}">
</head>
<body class="auth-page">
<div class="auth-shell">
    <div class="auth-card">
        {{-- Cette colonne de gauche affiche l identite visuelle de l application. --}}
        <aside class="auth-brand-panel">
            <div class="auth-brand-wave auth-brand-wave-one"></div>
            <div class="auth-brand-wave auth-brand-wave-two"></div>

            <div class="auth-brand-content">
                {{-- Ce bloc affiche l abreviation, le petit message de bienvenue et le nom de l application. --}}
                <div class="auth-brand-logo">DI</div>
                <p class="auth-brand-eyebrow">Bienvenue</p>
                <h1>DEV IA</h1>
            </div>
        </aside>

        {{-- Cette colonne de droite contient la connexion et la recuperation du mot de passe. --}}
        <section class="auth-login-panel">
            <div class="auth-login-head">
                {{-- Ce titre peut etre change ici si vous voulez modifier le texte visible en haut du formulaire. --}}
                <p>Connexion et acces utilisateur</p>
                <h2>DEV IA</h2>
            </div>

            {{-- Ce bloc affiche les messages de succes retournes par Laravel. --}}
            @if(session('success'))
                <div class="alert alert-success auth-alert">{{ session('success') }}</div>
            @endif

            {{-- Ce bloc affiche les erreurs de validation ou de connexion. --}}
            @if($errors->any())
                <div class="alert alert-danger auth-alert">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="auth-login-box">
                {{-- Ce panneau contient le formulaire de connexion principal. --}}
                <div class="auth-panel active" id="login-panel">
                    <form method="POST" action="{{ route('login.perform') }}" class="auth-form-grid">
                        @csrf
                        {{-- Ce champ accepte soit l email soit le code de connexion attribue a l utilisateur. --}}
                        <div>
                            <label for="login">Email et code de connexion</label>
                            <input id="login" type="text" name="login" value="{{ old('login') }}" placeholder="Entrez votre email ou votre code" required>
                        </div>
                        {{-- Ce champ permet de saisir le mot de passe du compte. --}}
                        <div>
                            <label for="password">Mot de passe</label>
                            <input id="password" type="password" name="password" placeholder="Entrez votre mot de passe" required>
                        </div>
                        {{-- Cette case permet de garder la session ouverte plus longtemps. --}}
                        <label class="auth-remember">
                            <input type="checkbox" name="remember">
                            <span>Se souvenir de moi</span>
                        </label>
                        {{-- Ce lien ouvre la recuperation du mot de passe oublie. --}}
                        <a href="{{ route('password.request') }}" class="auth-forgot-link">Mot de passe oublie ?</a>
                        {{-- Ce bouton envoie le formulaire de connexion. --}}
                        <button class="btn auth-submit-btn" type="submit">Login</button>
                    </form>
                </div>

            </div>
        </section>
    </div>
</div>

<script>
    // Aucun script de bascule n est necessaire ici car les deux formulaires restent visibles.
</script>
</body>
</html>
