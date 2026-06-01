<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\View;

// Ce contrôleur gère la connexion, la recuperation du mot de passe et la deconnexion.
class AuthController extends Controller
{
    // Le constructeur injecte le service qui garde une trace des connexions et deconnexions.
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    // Cette methode affiche la page de connexion simple avec le lien mot de passe oublie.
    public function showLogin(): View
    {
        $canSelfRegister = false;

        return view('auth.login', compact('canSelfRegister'));
    }

    // Cette methode authentifie l utilisateur soit par email soit par code de connexion.
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        try {
            $login = trim($credentials['login']);
            $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'adresse_email' : 'code_connexion';
            $user = User::with('roleModel')->where($field, $login)->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                return back()->withErrors(['login' => 'Identifiants invalides.'])->onlyInput('login');
            }

            // Cette ligne force la coherence entre le role relationnel et l ancienne colonne texte.
            $user->synchroniserRoleLegacy();

            // Cette sauvegarde ne se fait que si une correction du role texte est necessaire.
            if ($user->isDirty('role')) {
                $user->save();
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $request->user()->update(['last_login_at' => now()]);
            $this->activityService->log('connexion_utilisateur', $request->user());

            return redirect()->route('dashboard');
        } catch (QueryException $exception) {
            if ($this->isDatabaseUnavailable($exception)) {
                return $this->databaseUnavailableResponse($request, 'Connexion impossible pour le moment : la base de donnees ne repond pas.');
            }

            throw $exception;
        }
    }

    // Cette methode bloque l inscription libre pour garder une page de connexion simple.
    public function register(Request $request): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'register' => 'La creation libre de compte est desactivee.',
        ]);
    }

    // Cette methode affiche le formulaire de demande de reinitialisation du mot de passe.
    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    // Cette methode envoie le lien de reinitialisation par email.
    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::broker('users')->sendResetLink([
                'adresse_email' => $data['email'],
            ]);
        } catch (QueryException $exception) {
            if ($this->isDatabaseUnavailable($exception)) {
                return $this->databaseUnavailableResponse($request, 'Impossible d envoyer le lien : la base de donnees ne repond pas.');
            }

            throw $exception;
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Le lien de reinitialisation a ete envoye a votre adresse email.');
        }

        return back()->withErrors([
            'email' => 'Impossible d envoyer le lien de reinitialisation. Verifiez cette adresse email.',
        ]);
    }

    // Cette methode affiche le formulaire de definition du nouveau mot de passe.
    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    // Cette methode enregistre le nouveau mot de passe apres verification du jeton.
    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        try {
            $status = Password::broker('users')->reset(
                [
                    'adresse_email' => $data['email'],
                    'password' => $data['password'],
                    'password_confirmation' => $data['password_confirmation'],
                    'token' => $data['token'],
                ],
                function (User $user, string $password): void {
                    $user->forceFill([
                        'password' => $password,
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );
        } catch (QueryException $exception) {
            if ($this->isDatabaseUnavailable($exception)) {
                return $this->databaseUnavailableResponse($request, 'Impossible de reinitialiser le mot de passe : la base de donnees ne repond pas.');
            }

            throw $exception;
        }

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Votre mot de passe a ete reinitialise avec succes.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => Lang::get($status),
        ]);
    }

    // Cette methode ferme la session et nettoie les donnees de securite.
    public function logout(Request $request): RedirectResponse
    {
        $this->activityService->log('deconnexion_utilisateur', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Cette methode fabrique un code de connexion lisible et unique pour le premier administrateur.
    private function generateLoginCode(string $prefix = 'GA-'): string
    {
        do {
            $code = $prefix . strtoupper(Str::random(6));
        } while (User::where('code_connexion', $code)->exists());

        return $code;
    }

    // Cette methode detecte les pannes de connexion MySQL pour eviter les grands ecrans d erreur.
    private function isDatabaseUnavailable(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'sqlstate[hy000] [2002]')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'ordinateur cible l a expressement refusee')
            || str_contains($message, 'can\'t connect to mysql server');
    }

    // Cette methode renvoie un message propre a l utilisateur au lieu d une page technique.
    private function databaseUnavailableResponse(Request $request, string $message): RedirectResponse
    {
        return back()
            ->withErrors(['database' => $message])
            ->withInput($request->except('password', 'password_confirmation'));
    }
}
