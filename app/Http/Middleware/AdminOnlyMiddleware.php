<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Ce middleware verrouille les zones strictement réservées à l'administrateur.
class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() && $request->user()->isAdministrator(), 403);

        return $next($request);
    }
}
