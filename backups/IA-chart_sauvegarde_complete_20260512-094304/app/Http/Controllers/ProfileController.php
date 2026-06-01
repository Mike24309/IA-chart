<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// Ce controleur permet a chaque utilisateur de modifier son profil simple.
class ProfileController extends Controller
{
    public function __construct(private readonly ActivityService $activityService)
    {
    }

    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:utilisateurs,adresse_email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($request->user()->profile_photo_path) {
                Storage::disk('public')->delete($request->user()->profile_photo_path);
            }

            $payload['profile_photo_path'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $request->user()->update($payload);
        $this->activityService->log('modification_profil', $request->user());

        return redirect()->route('profile.edit')->with('success', 'Profil mis a jour.');
    }
}
