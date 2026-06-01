@extends('layouts.app')
{{-- Cette vue contient le formulaire de creation ou de modification d un produit. --}}
@section('page-title', $product->exists ? 'Modifier produit' : 'Creer produit')
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="card form-grid">
    @csrf
    @if($product->exists) @method('PUT') @endif
    <div><label>Nom</label><input type="text" name="name" value="{{ old('name', $product->name) }}" required></div>
    <div><label>Reference</label><input type="text" name="reference" value="{{ old('reference', $product->reference) }}" required></div>
    <div><label>Categorie</label><select name="category_id"><option value="">Aucune</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
    <div><label>Photo</label><input type="file" name="photo"></div>
    <div><label>Prix achat</label><input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required></div>
    <div><label>Prix vente</label><input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? 0) }}" required></div>
    <div><label>Stock</label><input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required></div>
    <div><label>Stock minimum</label><input type="number" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? $appSettings->global_minimum_stock) }}" required></div>
    <div class="full"><label>Description</label><textarea name="description" required>{{ old('description', $product->description) }}</textarea></div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
