<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele trace l'historique des rapports PDF IA generes.
class HistoriqueRapportIa extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'historiques_rapports_ia';

    protected array $mappageAnciensAttributs = [
        'log_ia_id' => 'journal_ia_id',
        'user_id' => 'utilisateur_id',
        'report_title' => 'titre_rapport',
        'generated_at' => 'genere_le',
    ];

    protected $fillable = [
        'journal_ia_id',
        'utilisateur_id',
        'titre_rapport',
        'genere_le',
    ];

    protected $casts = [
        'genere_le' => 'datetime',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(LogIa::class, 'journal_ia_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
