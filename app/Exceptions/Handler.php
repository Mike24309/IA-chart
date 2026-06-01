<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (QueryException $exception, Request $request) {
            if (! $this->isDatabaseUnavailable($exception)) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Le service de base de donnees est temporairement indisponible.',
                ], 503);
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'database' => 'La base de donnees est temporairement indisponible. Verifiez MySQL puis reessayez.',
                ]);
        });
    }

    // Cette methode reconnait les erreurs de connexion MySQL pour afficher un message propre.
    private function isDatabaseUnavailable(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'sqlstate[hy000] [2002]')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'ordinateur cible l a expressement refusee')
            || str_contains($message, 'can\'t connect to mysql server');
    }
}
