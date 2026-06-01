<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele represente une ligne de facture.
class FactureDetail extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'details_facture';

    protected array $mappageAnciensAttributs = [
        'quantity' => 'quantite',
        'unit_price_ht' => 'prix_unitaire_ht',
        'line_total_ht' => 'total_ligne_ht',
    ];

    protected $fillable = [
        'facture_id',
        'produit_id',
        'description',
        'quantity',
        'unit_price_ht',
        'line_total_ht',
    ];

    protected $casts = [
        'unit_price_ht' => 'decimal:2',
        'line_total_ht' => 'decimal:2',
    ];

    // Un detail appartient a une facture.
    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    // Un detail appartient a un produit.
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
