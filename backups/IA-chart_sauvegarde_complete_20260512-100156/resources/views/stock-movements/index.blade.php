@extends('layouts.app')
{{-- Cette vue affiche l historique des mouvements de stock avec les filtres. --}}

@section('page-title', 'Mouvements de stock')
@section('page-description', 'Tracabilite des entrees, sorties et ajustements')
@section('content')
<div class="card">
    <div class="toolbar">
        <form method="GET" class="toolbar-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Produit, reference, type ou utilisateur">
            <button class="btn btn-secondary" type="submit">Filtrer</button>
            <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary">Reinitialiser</a>
        </form>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Type</th>
                    <th>Qte</th>
                    <th>Stock</th>
                    <th>Utilisateur</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            @foreach($movements as $movement)
                <tr>
                    <td>{{ $movement->produit->name }}</td>
                    <td>
                        <span class="status-badge {{ $movement->movement_type === 'entree' ? 'status-success' : ($movement->movement_type === 'sortie' ? 'status-danger' : 'status-warning') }}">
                            {{ $movement->movement_type }}
                        </span>
                    </td>
                    <td>{{ $movement->quantity }}</td>
                    <td>{{ $movement->stock_avant }} -> {{ $movement->stock_apres }}</td>
                    <td>{{ $movement->user->name }}</td>
                    <td>{{ $movement->movement_date->format('d/m/Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $movements->links() }}
</div>
@endsection
