<?php

namespace App\Services;

use App\Models\ActiviteUtilisateur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

// Ce service centralise l'enregistrement des activités utilisateur.
class ActivityService
{
    public function log(string $action, ?Model $target = null, array $metadata = []): void
    {
        ActiviteUtilisateur::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
