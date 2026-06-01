@extends('layouts.app')
{{-- Cette vue contient le formulaire de creation ou de modification d un client. --}}
@section('page-title', $client->exists ? 'Modifier client' : 'Creer client')
@section('content')
<form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}" class="card form-grid">
    @csrf
    @if($client->exists) @method('PUT') @endif
    <div><label>Nom</label><input type="text" name="name" value="{{ old('name', $client->name) }}" required></div>
    <div><label>Postnom</label><input type="text" name="post_name" value="{{ old('post_name', $client->post_name) }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', $client->email) }}"></div>
    <div><label>Telephone</label><input type="text" name="phone" value="{{ old('phone', $client->phone) }}"></div>
    <div><label>Entreprise</label><input type="text" name="company" value="{{ old('company', $client->company) }}"></div>
    <div class="full"><label>Adresse</label><textarea name="address">{{ old('address', $client->address) }}</textarea></div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
