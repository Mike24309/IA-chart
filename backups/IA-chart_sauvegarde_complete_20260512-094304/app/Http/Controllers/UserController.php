<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

// Ce controleur est reserve a l administration des utilisateurs.
class UserController extends Controller
{
    // Le constructeur injecte le service charge d enregistrer les traces d administration.
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    // Cette methode affiche la liste paginee des utilisateurs avec leur role.
    public function index(Request $request): View
    {
        $users = User::with('roleModel')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim((string) $request->string('search')) . '%';

                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('nom', 'like', $search)
                        ->orWhere('adresse_email', 'like', $search)
                        ->orWhere('code_connexion', 'like', $search)
                        ->orWhere('telephone', 'like', $search);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    // Cette methode prepare le formulaire de creation d un nouvel utilisateur.
    public function create(): View
    {
        $this->ensureDefaultRoles();
        $roles = Role::orderBy('libelle')->get();

        return view('users.form', ['userModel' => new User(), 'roles' => $roles]);
    }

    // Cette methode valide les donnees, cree l utilisateur et lui attribue un code de connexion si besoin.
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:utilisateurs,adresse_email'],
            'login_code' => ['nullable', 'string', 'max:50', 'unique:utilisateurs,code_connexion'],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->messagesValidation(), $this->attributsValidation());

        $user = User::create([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'login_code' => $data['login_code'] ?: $this->generateLoginCode(),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityService->log('creation_utilisateur', $user);

        return redirect()->route('users.index')->with('success', 'Utilisateur cree.');
    }

    // Cette methode charge un utilisateur existant dans le formulaire de modification.
    public function edit(User $user): View
    {
        $this->ensureDefaultRoles();
        $roles = Role::orderBy('libelle')->get();

        return view('users.form', ['userModel' => $user, 'roles' => $roles]);
    }

    // Cette methode met a jour les informations d un utilisateur sans casser son mot de passe si aucun nouveau mot de passe n est fourni.
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:utilisateurs,adresse_email,' . $user->id],
            'login_code' => ['nullable', 'string', 'max:50', 'unique:utilisateurs,code_connexion,' . $user->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], $this->messagesValidation(), $this->attributsValidation());

        $payload = [
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'login_code' => $data['login_code'] ?: $user->login_code ?: $this->generateLoginCode(),
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $this->activityService->log('modification_utilisateur', $user);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis a jour.');
    }

    // Cette methode supprime un utilisateur sauf si l administrateur tente de supprimer son propre compte.
    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $user->delete();
        $this->activityService->log('suppression_utilisateur', $user);

        return redirect()->route('users.index')->with('success', 'Utilisateur supprime.');
    }

    // Cette methode genere un code de connexion unique pour les utilisateurs internes.
    private function generateLoginCode(): string
    {
        do {
            $code = 'USR-' . strtoupper(Str::random(6));
        } while (User::where('code_connexion', $code)->exists());

        return $code;
    }

    // Cette methode garantit la presence des roles de base necessaires au bon fonctionnement des comptes.
    private function ensureDefaultRoles(): void
    {
        Role::firstOrCreate(
            ['nom' => 'admin'],
            ['libelle' => 'Administrateur', 'description' => 'Acces total a l application.']
        );

        Role::firstOrCreate(
            ['nom' => 'employee'],
            ['libelle' => 'Employe', 'description' => 'Acces operationnel limite.']
        );
    }

    // Cette methode centralise les messages de validation affiches a l administrateur.
    private function messagesValidation(): array
    {
        return [
            'role_id.required' => 'Veuillez choisir un role.',
            'role_id.exists' => 'Le role selectionne est invalide.',
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L email est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'email.unique' => 'Cette adresse email existe deja.',
            'login_code.unique' => 'Ce code de connexion existe deja.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caracteres.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }

    // Cette methode donne des noms simples aux champs dans les messages d erreur.
    private function attributsValidation(): array
    {
        return [
            'role_id' => 'role',
            'name' => 'nom',
            'email' => 'adresse email',
            'login_code' => 'code de connexion',
            'phone' => 'telephone',
            'password' => 'mot de passe',
            'password_confirmation' => 'confirmation du mot de passe',
        ];
    }
}
