<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Ce middleware coupe l'accès aux comptes désactivés.
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Votre compte est désactivé.',
            ]);
        }

        return $next($request);
    }
}
