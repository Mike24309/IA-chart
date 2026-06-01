<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele represente un mouvement de stock.
class MouvementStock extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'mouvements_stock';

    protected array $mappageAnciensAttributs = [
        'user_id' => 'utilisateur_id',
        'movement_type' => 'type_mouvement',
        'quantity' => 'quantite',
        'reason' => 'motif',
        'movement_date' => 'date_mouvement',
    ];

    protected $fillable = [
        'produit_id',
        'user_id',
        'movement_type',
        'quantity',
        'stock_avant',
        'stock_apres',
        'reason',
        'movement_date',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
    ];

    // Un mouvement appartient a un produit.
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    // Un mouvement appartient a un utilisateur.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
