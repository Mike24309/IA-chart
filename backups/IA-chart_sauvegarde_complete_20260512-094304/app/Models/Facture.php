<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ce modele represente une facture.
class Facture extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'factures';

    protected array $mappageAnciensAttributs = [
        'invoice_number' => 'numero_facture',
        'user_id' => 'utilisateur_id',
        'invoice_date' => 'date_facture',
        'due_date' => 'date_echeance',
        'tax_rate' => 'taux_tva',
        'tax_amount' => 'montant_tva',
        'discount_amount' => 'montant_remise',
        'status' => 'statut',
    ];

    protected $fillable = [
        'invoice_number',
        'client_id',
        'user_id',
        'invoice_date',
        'due_date',
        'total_ht',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_ttc',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_facture' => 'date',
        'date_echeance' => 'date',
        'total_ht' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_remise' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    // Une facture appartient a un client.
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    // Une facture appartient a un utilisateur.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    // Une facture possede plusieurs details.
    public function details(): HasMany
    {
        return $this->hasMany(FactureDetail::class, 'facture_id');
    }
}
