<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ce modele represente un role utilisateur.
class Role extends Model
{
    use MappeAnciensAttributs;

    protected array $mappageAnciensAttributs = [
        'name' => 'nom',
        'label' => 'libelle',
    ];

    // Ces champs autorisent l insertion et la mise a jour avec les vrais noms francais en base.
    protected $fillable = [
        'nom',
        'libelle',
        'description',
    ];

    // Un role peut etre attribue a plusieurs utilisateurs.
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
