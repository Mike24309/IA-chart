<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ce modele represente un produit gere dans le stock.
class Produit extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'produits';

    protected array $mappageAnciensAttributs = [
        'category_id' => 'categorie_id',
        'name' => 'nom',
        'purchase_price' => 'prix_achat',
        'sale_price' => 'prix_vente',
        'stock' => 'stock',
        'minimum_stock' => 'stock_minimum',
        'photo_path' => 'chemin_photo',
        'is_active' => 'est_actif',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'reference',
        'purchase_price',
        'sale_price',
        'stock',
        'minimum_stock',
        'photo_path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'prix_achat' => 'decimal:2',
        'prix_vente' => 'decimal:2',
        'est_actif' => 'boolean',
    ];

    // Un produit appartient a une categorie.
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_id');
    }

    // Un produit peut avoir plusieurs lignes de facture.
    public function factureDetails(): HasMany
    {
        return $this->hasMany(FactureDetail::class, 'produit_id');
    }

    // Un produit peut avoir plusieurs mouvements de stock.
    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class, 'produit_id');
    }

    // Cette methode indique si le produit est sous le stock minimum.
    public function isBelowMinimum(): bool
    {
        return (int) $this->stock <= (int) $this->stock_minimum;
    }
}
