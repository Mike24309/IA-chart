@extends('layouts.app')
{{-- Cette vue contient le formulaire de creation ou de modification d un utilisateur. --}}
@section('page-title', $userModel->exists ? 'Modifier utilisateur' : 'Creer utilisateur')
@section('content')
@php
    // Cette preparation choisit le role Employe par defaut lors de la creation d un nouvel utilisateur.
    $roleEmploye = $roles->firstWhere('nom', 'employee');
    $roleSelectionne = old('role_id', $userModel->role_id ?? ($roleEmploye->id ?? null));
@endphp
<form method="POST" action="{{ $userModel->exists ? route('users.update', $userModel) : route('users.store') }}" class="card form-grid">
    @csrf
    @if($userModel->exists)
        @method('PUT')
    @endif

    <div><label>Nom</label><input type="text" name="name" value="{{ old('name', $userModel->name) }}" required></div>
    <div><label>Email</label><input type="email" name="email" value="{{ old('email', $userModel->email) }}" required></div>
    <div><label>Code de connexion</label><input type="text" name="login_code" value="{{ old('login_code', $userModel->login_code) }}" placeholder="Laissez vide pour generation automatique"></div>
    <div><label>Telephone</label><input type="text" name="phone" value="{{ old('phone', $userModel->phone) }}"></div>
    <div>
        <label>Role</label>
        <select name="role_id" required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((string) $roleSelectionne === (string) $role->id)>{{ $role->libelle }}</option>
            @endforeach
        </select>
        <small class="muted">Par defaut, un nouvel utilisateur est cree comme employe.</small>
    </div>
    <div>
        <label>Mot de passe</label>
        <input type="password" name="password" {{ $userModel->exists ? '' : 'required' }}>
        <small class="muted">Utilisez au moins 6 caracteres.</small>
    </div>
    <div>
        <label>Confirmation</label>
        <input type="password" name="password_confirmation" {{ $userModel->exists ? '' : 'required' }}>
        <small class="muted">Retapez exactement le meme mot de passe.</small>
    </div>
    <label class="checkbox full"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $userModel->is_active ?? true) ? 'checked' : '' }}><span>Compte actif</span></label>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
@endsection
