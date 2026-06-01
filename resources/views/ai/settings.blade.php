@extends('layouts.app')
{{-- Cette vue permet de regler les parametres du module IA. --}}

@section('page-title', 'Parametres IA')
@section('page-description', 'Seuils, modele Gemini, cache et comportement du module IA')
@section('content')
<form method="POST" action="{{ route('ai.settings.update') }}" class="card form-grid">
    @csrf
    @method('PUT')
    <div><label>Seuil marge minimale</label><input type="number" step="0.01" name="minimum_margin_threshold" value="{{ old('minimum_margin_threshold', $settings->minimum_margin_threshold) }}" required></div>
    <div><label>Seuil stock critique</label><input type="number" name="critical_stock_threshold" value="{{ old('critical_stock_threshold', $settings->critical_stock_threshold) }}" required></div>
    <div><label>Sensibilite IA</label>
        <select name="sensitivity_level">
            <option value="faible" @selected(old('sensitivity_level', $settings->sensitivity_level) === 'faible')>Faible</option>
            <option value="moyenne" @selected(old('sensitivity_level', $settings->sensitivity_level) === 'moyenne')>Moyenne</option>
            <option value="elevee" @selected(old('sensitivity_level', $settings->sensitivity_level) === 'elevee')>Elevee</option>
        </select>
    </div>
    <div><label>Modele Gemini</label><input type="text" name="openrouter_model" value="{{ old('openrouter_model', $settings->openrouter_model) }}" required></div>
    <div><label>Duree cache (minutes)</label><input type="number" name="cache_duration_minutes" value="{{ old('cache_duration_minutes', $settings->cache_duration_minutes) }}" required></div>
    <div class="full form-checkbox-grid">
        <label class="checkbox"><input type="checkbox" name="enable_behavior_analysis" value="1" @checked(old('enable_behavior_analysis', $settings->enable_behavior_analysis))><span>Activer analyse comportementale</span></label>
        <label class="checkbox"><input type="checkbox" name="enable_daily_audit" value="1" @checked(old('enable_daily_audit', $settings->enable_daily_audit))><span>Activer audit quotidien automatique</span></label>
        <label class="checkbox"><input type="checkbox" name="enable_auto_alerts" value="1" @checked(old('enable_auto_alerts', $settings->enable_auto_alerts))><span>Activer alertes automatiques</span></label>
        <label class="checkbox"><input type="checkbox" name="enable_auto_pdf" value="1" @checked(old('enable_auto_pdf', $settings->enable_auto_pdf))><span>Activer generation PDF automatique</span></label>
        <label class="checkbox"><input type="checkbox" name="enable_realtime_mode" value="1" @checked(old('enable_realtime_mode', $settings->enable_realtime_mode))><span>Activer mode analyse temps reel</span></label>
    </div>
    <button class="btn btn-primary" type="submit">Enregistrer les parametres IA</button>
</form>
@endsection
