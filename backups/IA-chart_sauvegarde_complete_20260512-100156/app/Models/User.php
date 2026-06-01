<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

// Ce modele represente un utilisateur authentifie de l'application.
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, MappeAnciensAttributs;

    // Cette propriete garde en memoire la presence de l ancienne colonne role.
    protected static ?bool $colonneRoleLegacyDisponible = null;

    protected $table = 'utilisateurs';

    protected array $mappageAnciensAttributs = [
        'name' => 'nom',
        'email' => 'adresse_email',
        'login_code' => 'code_connexion',
        'phone' => 'telephone',
        'profile_photo_path' => 'chemin_photo_profil',
        'password' => 'mot_de_passe',
        'is_active' => 'est_actif',
        'last_login_at' => 'dernier_login_a',
    ];

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'login_code',
        'phone',
        'profile_photo_path',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'mot_de_passe',
        'remember_token',
    ];

    protected $casts = [
        'mot_de_passe' => 'hashed',
        'est_actif' => 'boolean',
        'dernier_login_a' => 'datetime',
    ];

    // Cette methode synchronise automatiquement l ancienne colonne texte "role"
    // avec le role relationnel pour eviter toute incoherence entre les deux.
    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $user->synchroniserRoleLegacy();
        });
    }

    // Un utilisateur appartient a un role.
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Un utilisateur peut creer plusieurs factures.
    public function factures()
    {
        return $this->hasMany(Facture::class, 'utilisateur_id');
    }

    // Un utilisateur peut enregistrer plusieurs mouvements de stock.
    public function mouvementsStock()
    {
        return $this->hasMany(MouvementStock::class, 'utilisateur_id');
    }

    // Un utilisateur peut avoir plusieurs activites enregistrees.
    public function activites()
    {
        return $this->hasMany(ActiviteUtilisateur::class, 'utilisateur_id');
    }

    // Cette methode indique si l'utilisateur est administrateur.
    public function isAdministrator(): bool
    {
        $role = $this->obtenirRoleRelationnel();

        return $role?->nom === 'admin' || $this->lireRoleLegacy() === 'admin';
    }

    // Cet attribut fournit un nom de role exploitable par les middlewares.
    public function getResolvedRoleNameAttribute(): ?string
    {
        $role = $this->obtenirRoleRelationnel();

        return $role?->nom ?? $this->lireRoleLegacy();
    }

    // Cet attribut fournit le libelle du role pour les vues.
    public function getResolvedRoleLabelAttribute(): string
    {
        $role = $this->obtenirRoleRelationnel();

        return $role?->libelle ?? ($this->isAdministrator() ? 'Administrateur' : 'Employe');
    }

    // Cette methode recupere proprement le role relationnel sans dupliquer le code.
    public function obtenirRoleRelationnel(): ?Role
    {
        return $this->relationLoaded('roleModel') ? $this->getRelation('roleModel') : $this->roleModel()->first();
    }

    // Cette methode copie le role relationnel vers l ancienne colonne texte encore presente en base.
    public function synchroniserRoleLegacy(): void
    {
        if (! $this->colonneRoleLegacyDisponible()) {
            return;
        }

        $role = $this->obtenirRoleRelationnel();

        if ($role?->nom === 'admin') {
            $this->setAttribute('role', 'admin');

            return;
        }

        if ($role?->nom === 'employee') {
            $this->setAttribute('role', 'user');
        }
    }

    // Cette methode lit l ancien role texte seulement si la colonne existe encore.
    private function lireRoleLegacy(): ?string
    {
        if (! $this->colonneRoleLegacyDisponible()) {
            return null;
        }

        return $this->getAttribute('role');
    }

    // Cette methode detecte une seule fois si l ancienne colonne role est encore presente en base.
    private function colonneRoleLegacyDisponible(): bool
    {
        if (static::$colonneRoleLegacyDisponible === null) {
            static::$colonneRoleLegacyDisponible = Schema::hasColumn($this->getTable(), 'role');
        }

        return static::$colonneRoleLegacyDisponible;
    }

    // Cette methode indique au systeme de reinitialisation quel email utiliser.
    public function getEmailForPasswordReset(): string
    {
        return (string) $this->adresse_email;
    }
}
