@extends('layouts.app')
{{-- Cette vue permet a l utilisateur de mettre a jour son profil et sa photo. --}}

@section('page-title', 'Mon profil')
@section('page-description', 'Informations personnelles et securite du compte')
@section('content')
<section class="profile-hero {{ auth()->user()->isAdministrator() ? 'profile-hero-admin' : 'profile-hero-employee' }}">
    <div class="profile-identity">
        {{-- Ce petit formulaire permet de changer la photo de profil directement en cliquant sur l avatar. --}}
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="profile-photo-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
            <input type="hidden" name="phone" value="{{ auth()->user()->phone }}">
            <label class="profile-photo-edit">
                @if(auth()->user()->profile_photo_path)
                    <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}?v={{ auth()->user()->updated_at?->timestamp ?? time() }}" alt="Photo" class="profile-hero-avatar">
                @else
                    <div class="profile-hero-avatar avatar-fallback">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                @endif
                <span>✎</span>
                <input type="file" name="profile_photo" accept="image/*" onchange="this.form.submit()">
            </label>
        </form>
        <div>
            {{-- Cette zone affiche le role et le texte de presentation du compte connecte. --}}
            <span class="eyebrow">{{ auth()->user()->resolved_role_label }}</span>
            <h3>{{ auth()->user()->name }}</h3>
            <p>{{ auth()->user()->isAdministrator() ? 'Vous disposez d un acces complet aux modules et au parametrage global.' : 'Vous disposez d un acces operationnel centre sur les ventes, les clients et le stock.' }}</p>
        </div>
    </div>
    <div class="profile-summary">
        {{-- Ces cartes resument l email, le code de connexion et la derniere connexion. --}}
        <div><span>Email</span><strong>{{ auth()->user()->email }}</strong></div>
        <div><span>Code connexion</span><strong>{{ auth()->user()->login_code ?: 'Non defini' }}</strong></div>
        <div><span>Derniere connexion</span><strong>{{ optional(auth()->user()->last_login_at)?->format('d/m/Y H:i') ?: 'N/A' }}</strong></div>
    </div>
</section>

{{-- Ce formulaire principal permet de modifier les informations du profil et le mot de passe. --}}
<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card form-grid">
    @csrf
    @method('PUT')
    <div><label>Nom</label><input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required></div>
    <div><label>Telephone</label><input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div>
    <div><label>Photo de profil</label><input type="file" name="profile_photo" accept="image/*"></div>
    <div><label>Nouveau mot de passe</label><input type="password" name="password"></div>
    <div><label>Confirmation</label><input type="password" name="password_confirmation"></div>
    <button class="btn btn-primary" type="submit">Mettre a jour</button>
</form>
@endsection
