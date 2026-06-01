@extends('layouts.app')
{{-- Cette vue affiche l historique des mouvements de stock avec les filtres. --}}

@section('page-title', 'Mouvements de stock')
@section('page-description', 'Tracabilite des entrees, sorties et ajustements')
@section('content')
<div class="card">
    <div class="toolbar">
        <form method="GET" class="toolbar-form" data-auto-filter-form>
            <div class="filter-field filter-field-search">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Produit, reference, type ou utilisateur" data-auto-filter-input>
            </div>
            <div class="filter-field filter-field-letter">
                <input type="text" name="letter" value="{{ request('letter') }}" placeholder="Lettre" maxlength="1" data-auto-filter-input>
            </div>
            <div class="filter-field filter-field-date">
                <input type="date" name="date" value="{{ request('date') }}">
            </div>
            <div class="filter-actions">
                <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary">Reinitialiser</a>
            </div>
        </form>
        @if(auth()->user()->isAdministrator())
            <a href="{{ route('stock-movements.create') }}" class="btn btn-primary">Nouveau mouvement</a>
        @endif
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
