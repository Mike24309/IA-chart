@extends('layouts.app')
{{-- Cette vue contient le formulaire de creation d un mouvement de stock. --}}
@section('page-title', 'Creer un mouvement de stock')
@section('content')
<form method="POST" action="{{ route('stock-movements.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>Produit</label>
        <select name="produit_id">
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Type</label>
        <select name="movement_type">
            <option value="entree">Entree</option>
            <option value="sortie">Sortie</option>
            <option value="ajustement">Ajustement</option>
        </select>
    </div>
    <div>
        <label>Quantite</label>
        <input type="number" name="quantity" required>
    </div>
    <div>
        <label>Nouveau stock (ajustement)</label>
        <input type="number" name="new_stock">
    </div>
    <div class="full">
        <label>Motif</label>
        <input type="text" name="reason" required>
    </div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
