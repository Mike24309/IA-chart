@extends('layouts.app')
{{-- Cette vue affiche le journal des activites et des actions sensibles realisees dans l application. --}}

@section('page-title', 'Journal d’activite')
@section('page-description', 'Traçabilité complète des opérations et actions sensibles')
@section('content')
<div class="card">
    <div class="toolbar">
        <form method="GET" class="toolbar-form toolbar-form-wide">
            <div class="filter-field filter-field-search">
                <input type="text" name="action" value="{{ request('action') }}" placeholder="Rechercher une action">
            </div>
            <div class="filter-field filter-field-select">
                <select name="user_id">
                    <option value="">Tous les utilisateurs</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-secondary" type="submit">Filtrer</button>
                <a href="{{ route('activities.index') }}" class="btn btn-secondary">Reinitialiser</a>
            </div>
        </form>
        <span class="pill">Total: {{ $activities->total() }}</span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Cible</th>
                <th>Contexte</th>
            </tr>
            </thead>
            <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $activity->user?->name ?? 'Systeme' }}</td>
                    <td><span class="status-badge status-info">{{ str_replace('_', ' ', $activity->action) }}</span></td>
                    <td>{{ class_basename((string) $activity->target_type) ?: 'N/A' }} @if($activity->target_id)#{{ $activity->target_id }}@endif</td>
                    <td class="muted">{{ $activity->ip_address }}<br>{{ \Illuminate\Support\Str::limit((string) $activity->user_agent, 60) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Aucune activite enregistree pour les filtres selectionnes.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $activities->links() }}
</div>
@endsection
