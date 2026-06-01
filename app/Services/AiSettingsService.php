<?php

namespace App\Services;

use App\Models\ParametreIa;

// Ce service fournit et initialise les paramètres du module IA.
class AiSettingsService
{
    public function current(): ParametreIa
    {
        $settings = ParametreIa::firstOrCreate(
            ['id' => 1],
            [
                'minimum_margin_threshold' => 5,
                'critical_stock_threshold' => 5,
                'sensitivity_level' => 'moyenne',
                'enable_behavior_analysis' => true,
                'enable_daily_audit' => false,
                'openrouter_model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
                'cache_duration_minutes' => 30,
                'enable_auto_alerts' => true,
                'enable_auto_pdf' => false,
                'enable_realtime_mode' => true,
            ]
        );

        // Cette correction remplace automatiquement un ancien modele OpenRouter par un modele Gemini valide.
        if (! str_starts_with((string) $settings->modele_openrouter, 'gemini-')) {
            $settings->update([
                'openrouter_model' => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
            ]);
            $settings->refresh();
        }

        return $settings;
    }
}
