<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ce modele represente une categorie de produit.
class Category extends Model
{
    use MappeAnciensAttributs;

    protected array $mappageAnciensAttributs = [
        'name' => 'nom',
        'is_active' => 'est_active',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'est_active' => 'boolean',
    ];

    // Une categorie peut contenir plusieurs produits.
    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'categorie_id');
    }
}
