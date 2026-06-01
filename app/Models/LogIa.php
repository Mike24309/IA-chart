<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Ce modele conserve les executions du module IA et leurs resultats.
class LogIa extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'journaux_ia';

    protected array $mappageAnciensAttributs = [
        'user_id' => 'utilisateur_id',
        'log_type' => 'type_journal',
        'status' => 'statut',
        'input_signature' => 'signature_entree',
        'input_payload' => 'charge_entree',
        'output_payload' => 'charge_sortie',
        'error_message' => 'message_erreur',
        'generated_at' => 'genere_le',
    ];

    protected $fillable = [
        'utilisateur_id',
        'type_journal',
        'statut',
        'signature_entree',
        'charge_entree',
        'charge_sortie',
        'message_erreur',
        'genere_le',
    ];

    protected $casts = [
        'charge_entree' => 'array',
        'charge_sortie' => 'array',
        'genere_le' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationIa::class, 'journal_ia_id');
    }
}
