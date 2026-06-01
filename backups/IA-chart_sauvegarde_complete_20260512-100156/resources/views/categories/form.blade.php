@extends('layouts.app')
{{-- Cette vue contient le formulaire de creation ou de modification d une categorie. --}}
@section('page-title', $category->exists ? 'Modifier catégorie' : 'Créer catégorie')
@section('content')
<form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card form-grid">
    @csrf
    @if($category->exists) @method('PUT') @endif
    <div><label>Nom</label><input type="text" name="name" value="{{ old('name', $category->name) }}" required></div>
    <div class="full"><label>Description</label><textarea name="description">{{ old('description', $category->description) }}</textarea></div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
