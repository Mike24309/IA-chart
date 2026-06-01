@extends('layouts.app')
{{-- Cette vue affiche la liste des produits avec le stock, les filtres et les actions. --}}

@section('page-title', auth()->user()->isAdministrator() ? 'Produits' : 'Stock disponible')
@section('page-description', auth()->user()->isAdministrator() ? 'Gestion complete des produits' : 'Consultation simple des produits disponibles a la vente')
@section('content')
<div class="card">
    <div class="toolbar">
        {{-- Ce petit formulaire permet de rechercher rapidement un produit par son nom ou sa reference. --}}
        <form method="GET" class="toolbar-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Produit, reference, categorie ou description">
            <button class="btn btn-secondary" type="submit">Filtrer</button>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Reinitialiser</a>
        </form>
    </div>
    {{-- Ce tableau affiche la liste des produits avec une version differente selon le role. --}}
    <div class="table-responsive">
        <table>
            <thead>
            @if(auth()->user()->isAdministrator())
                <tr><th>Produit</th><th>Categorie</th><th>Prix vente</th><th>Stock</th><th>Actions</th></tr>
            @else
                <tr><th>Produit</th><th>Categorie</th><th>Prix vente</th><th>Stock disponible</th><th>Etat</th></tr>
            @endif
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}<div class="muted">{{ $product->reference }}</div></td>
                    <td>{{ $product->category?->name }}</td>
                    <td>{{ number_format($product->sale_price, 2) }}</td>
                    @if(auth()->user()->isAdministrator())
                        {{-- Ces actions permettent a l administrateur de modifier ou supprimer un produit. --}}
                        <td>{{ $product->stock }}</td>
                        <td class="actions">
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary">Modifier</a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    @else
                        {{-- Cette colonne montre a l employe si le produit est disponible ou en stock bas. --}}
                        <td>{{ $product->stock }}</td>
                        <td>
                            @if($product->isBelowMinimum())
                                <span class="status-badge status-danger">Stock bas</span>
                            @else
                                <span class="status-badge status-success">Disponible</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{-- Cette pagination permet de parcourir la liste des produits page par page. --}}
    {{ $products->links() }}
</div>
@endsection
