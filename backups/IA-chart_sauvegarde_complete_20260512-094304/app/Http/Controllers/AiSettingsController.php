<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use App\Services\AiSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ce contrôleur permet à l'administrateur de configurer le module IA.
class AiSettingsController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $aiSettingsService,
        private readonly ActivityService $activityService
    ) {
    }

    public function edit(): View
    {
        $settings = $this->aiSettingsService->current();

        return view('ai.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = $this->aiSettingsService->current();

        $data = $request->validate([
            'minimum_margin_threshold' => ['required', 'numeric', 'min:0'],
            'critical_stock_threshold' => ['required', 'integer', 'min:0'],
            'sensitivity_level' => ['required', 'in:faible,moyenne,elevee'],
            'openrouter_model' => ['required', 'string', 'max:255'],
            'cache_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $data['enable_behavior_analysis'] = $request->boolean('enable_behavior_analysis');
        $data['enable_daily_audit'] = $request->boolean('enable_daily_audit');
        $data['enable_auto_alerts'] = $request->boolean('enable_auto_alerts');
        $data['enable_auto_pdf'] = $request->boolean('enable_auto_pdf');
        $data['enable_realtime_mode'] = $request->boolean('enable_realtime_mode');

        $settings->update($data);
        $this->activityService->log('modification_parametres_ia', $settings);

        return redirect()->route('ai.settings.edit')->with('success', 'Parametres IA mis a jour.');
    }
}
