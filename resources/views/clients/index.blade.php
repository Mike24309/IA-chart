@extends('layouts.app')
{{-- Cette vue affiche la liste des clients avec les actions principales. --}}
@section('page-title', 'Clients')
@section('content')
<div class="card">
    <div class="toolbar">
        <form method="GET" class="toolbar-form" data-auto-filter-form>
            <div class="filter-field filter-field-search">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, postnom, email, telephone ou entreprise" data-auto-filter-input>
            </div>
            <div class="filter-field filter-field-letter">
                <input type="text" name="letter" value="{{ request('letter') }}" placeholder="Lettre" maxlength="1" data-auto-filter-input>
            </div>
            <div class="filter-actions">
                <a href="{{ route('clients.index') }}" class="btn btn-secondary">Reinitialiser</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Nom complet</th><th>Email</th><th>Telephone</th><th>Entreprise</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ trim($client->name . ' ' . ($client->post_name ?? '')) }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->company }}</td>
                    <td class="actions">
                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary">Modifier</a>
                        @if(auth()->user()->isAdministrator())
                            <form method="POST" action="{{ route('clients.destroy', $client) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-danger">Supprimer</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $clients->links() }}
</div>
@endsection
