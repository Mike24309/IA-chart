@extends('layouts.app')
{{-- Cette vue affiche la liste des categories et les actions de gestion. --}}
@section('page-title', 'Catégories')
@section('content')
<div class="card">
    <div class="toolbar">
        <form method="GET" class="toolbar-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom ou description">
            <button class="btn btn-secondary" type="submit">Filtrer</button>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Reinitialiser</a>
        </form>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Nom</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description }}</td>
                    <td class="actions">
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-secondary">Modifier</a>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-danger">Supprimer</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $categories->links() }}
</div>
@endsection
