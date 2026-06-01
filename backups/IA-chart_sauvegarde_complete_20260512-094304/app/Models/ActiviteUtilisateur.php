<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele represente une ligne du journal d'activite utilisateur.
class ActiviteUtilisateur extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'activites_utilisateurs';

    protected array $mappageAnciensAttributs = [
        'user_id' => 'utilisateur_id',
        'target_type' => 'type_cible',
        'target_id' => 'cible_id',
        'metadata' => 'metadonnees',
        'ip_address' => 'adresse_ip',
        'user_agent' => 'agent_utilisateur',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadonnees' => 'array',
    ];

    // Une activite peut appartenir a un utilisateur.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
