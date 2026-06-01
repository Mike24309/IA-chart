@extends('layouts.app')
{{-- Cette vue affiche la liste des factures avec les filtres et les actions disponibles. --}}

@section('page-title', auth()->user()->isAdministrator() ? 'Factures' : 'Mes factures')
@section('page-description', auth()->user()->isAdministrator() ? 'Historique complet des factures avec filtres et actions de gestion' : 'Historique de vos factures avec recherche rapide')
@section('content')
<div class="card stack">
    <div class="toolbar">
        <div>
            {{-- Ce texte explique le contenu de la page selon le role connecte. --}}
            <strong>{{ auth()->user()->isAdministrator() ? 'Liste complete des factures' : 'Liste de mes factures' }}</strong>
        </div>
    </div>

    {{-- Ce formulaire permet de filtrer les factures par texte. --}}
    <form method="GET" class="form-grid">
        <div>
            <label>Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Numero, client ou commercial">
        </div>
        <div class="actions" style="align-items: end;">
            <button class="btn btn-secondary" type="submit">Filtrer</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Reinitialiser</a>
        </div>
    </form>

    {{-- Ce tableau affiche la liste complete des factures trouvees apres filtrage. --}}
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Numero</th>
                    <th>Client</th>
                    <th>Creee par</th>
                    <th>Date</th>
                    <th>Total TTC</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->client->name }}</td>
                    <td>{{ $invoice->user->name }}</td>
                    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    <td>{{ number_format($invoice->total_ttc, 2) }} {{ $appSettings->currency }}</td>
                    <td class="actions">
                        {{-- Ces boutons ouvrent l affichage detaille ou le PDF de la facture. --}}
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Voir</a>
                        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary">PDF</a>
                        @if(auth()->user()->isAdministrator())
                            {{-- Cette suppression est reservee a l administrateur. --}}
                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Supprimer cette facture et remettre le stock a jour ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Aucune facture trouvee pour ces filtres.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Cette pagination permet de passer d une page de factures a une autre. --}}
    {{ $invoices->appends(request()->query())->links() }}
</div>
@endsection
