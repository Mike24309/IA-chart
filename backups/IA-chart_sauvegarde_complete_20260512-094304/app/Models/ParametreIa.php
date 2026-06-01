<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;

// Ce modele represente les parametres de fonctionnement du module IA.
class ParametreIa extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'parametres_ia';

    protected array $mappageAnciensAttributs = [
        'minimum_margin_threshold' => 'seuil_marge_minimale',
        'critical_stock_threshold' => 'seuil_stock_critique',
        'sensitivity_level' => 'niveau_sensibilite',
        'enable_behavior_analysis' => 'activer_analyse_comportementale',
        'enable_daily_audit' => 'activer_audit_quotidien',
        'openrouter_model' => 'modele_openrouter',
        'cache_duration_minutes' => 'duree_cache_minutes',
        'enable_auto_alerts' => 'activer_alertes_automatiques',
        'enable_auto_pdf' => 'activer_pdf_automatique',
        'enable_realtime_mode' => 'activer_mode_temps_reel',
    ];

    protected $fillable = [
        'minimum_margin_threshold',
        'critical_stock_threshold',
        'sensitivity_level',
        'enable_behavior_analysis',
        'enable_daily_audit',
        'openrouter_model',
        'cache_duration_minutes',
        'enable_auto_alerts',
        'enable_auto_pdf',
        'enable_realtime_mode',
    ];

    protected $casts = [
        'seuil_marge_minimale' => 'decimal:2',
        'activer_analyse_comportementale' => 'boolean',
        'activer_audit_quotidien' => 'boolean',
        'activer_alertes_automatiques' => 'boolean',
        'activer_pdf_automatique' => 'boolean',
        'activer_mode_temps_reel' => 'boolean',
    ];
}
