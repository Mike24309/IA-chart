@extends('layouts.app')
{{-- Cette vue affiche la liste des utilisateurs avec leur role et leur statut. --}}

@section('page-title', 'Utilisateurs')
@section('page-description', 'Gestion des comptes, des roles, des statuts et des codes de connexion')
@section('content')
<div class="card">
    <div class="toolbar">
        <form method="GET" class="toolbar-form" data-auto-filter-form>
            <div class="filter-field filter-field-search">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, code ou telephone" data-auto-filter-input>
            </div>
            <div class="filter-field filter-field-letter">
                <input type="text" name="letter" value="{{ request('letter') }}" placeholder="Lettre" maxlength="1" data-auto-filter-input>
            </div>
            <div class="filter-field filter-field-date">
                <input type="date" name="date" value="{{ request('date') }}">
            </div>
            <div class="filter-actions">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Reinitialiser</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Nom</th><th>Email</th><th>Code connexion</th><th>Role</th><th>Statut</th><th>Derniere connexion</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>
                        <div class="user-inline">
                            @if($user->profile_photo_path)
                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}?v={{ $user->updated_at?->timestamp ?? time() }}" alt="Photo" class="profile-avatar">
                            @else
                                <div class="profile-avatar avatar-fallback">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            @endif
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <div class="muted">{{ $user->phone ?: 'Telephone non renseigne' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td><span class="pill">{{ $user->login_code ?: 'Non defini' }}</span></td>
                    <td><span class="status-badge {{ $user->isAdministrator() ? 'status-admin' : 'status-employee' }}">{{ $user->resolved_role_label }}</span></td>
                    <td><span class="status-badge {{ $user->is_active ? 'status-success' : 'status-danger' }}">{{ $user->is_active ? 'Actif' : 'Suspendu' }}</span></td>
                    <td>{{ optional($user->last_login_at)?->format('d/m/Y H:i') ?: 'Jamais' }}</td>
                    <td class="actions">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary">Modifier</a>
                        <form method="POST" action="{{ route('users.destroy', $user) }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Supprimer</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
