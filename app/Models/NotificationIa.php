<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele represente une alerte generee automatiquement par l'IA.
class NotificationIa extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'notifications_ia';

    protected array $mappageAnciensAttributs = [
        'log_ia_id' => 'journal_ia_id',
        'title' => 'titre',
        'severity' => 'niveau_gravite',
        'is_read' => 'est_lue',
        'notified_at' => 'notifiee_le',
    ];

    protected $fillable = [
        'journal_ia_id',
        'titre',
        'message',
        'niveau_gravite',
        'est_lue',
        'notifiee_le',
    ];

    protected $casts = [
        'est_lue' => 'boolean',
        'notifiee_le' => 'datetime',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(LogIa::class, 'journal_ia_id');
    }
}
