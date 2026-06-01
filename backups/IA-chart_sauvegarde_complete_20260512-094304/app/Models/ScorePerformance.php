<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Ce modele historise le score global calcule par le module IA.
class ScorePerformance extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'scores_performance';

    protected array $mappageAnciensAttributs = [
        'log_ia_id' => 'journal_ia_id',
        'global_score' => 'score_global',
        'score_breakdown' => 'detail_score',
        'score_explanation' => 'explication_score',
    ];

    protected $fillable = [
        'journal_ia_id',
        'score_global',
        'detail_score',
        'explication_score',
    ];

    protected $casts = [
        'detail_score' => 'array',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(LogIa::class, 'journal_ia_id');
    }
}
