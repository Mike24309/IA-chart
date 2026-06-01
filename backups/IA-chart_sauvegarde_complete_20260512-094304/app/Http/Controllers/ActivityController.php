<?php

namespace App\Http\Controllers;

use App\Models\ActiviteUtilisateur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Ce contrôleur permet à l'administrateur de consulter le journal d'activité global.
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = ActiviteUtilisateur::with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', '%' . $request->string('action') . '%'))
            ->when($request->filled('user_id'), fn ($query) => $query->where('utilisateur_id', $request->integer('user_id')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $users = User::orderBy('nom')->get(['id', 'nom']);

        return view('activities.index', compact('activities', 'users'));
    }
}
