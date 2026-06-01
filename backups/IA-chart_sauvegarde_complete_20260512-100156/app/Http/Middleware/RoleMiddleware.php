<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Ce middleware bloque l'accès si l'utilisateur n'a pas le rôle attendu.
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user(), 403);

        $roleName = $request->user()->resolved_role_name;
        abort_unless(in_array($roleName, $roles, true), 403);

        return $next($request);
    }
}
