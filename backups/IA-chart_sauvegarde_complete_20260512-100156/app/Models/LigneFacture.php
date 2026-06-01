<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele represente une ligne de facture associee a un produit et a une quantite.
class LigneFacture extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'details_facture';

    protected array $mappageAnciensAttributs = [
        'quantity' => 'quantite',
        'unit_price' => 'prix_unitaire_ht',
        'line_total' => 'total_ligne_ht',
    ];

    protected $fillable = [
        'facture_id',
        'produit_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'prix_unitaire_ht' => 'decimal:2',
        'total_ligne_ht' => 'decimal:2',
    ];

    // Une ligne appartient a une facture.
    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    // Une ligne reference un produit vendu.
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
