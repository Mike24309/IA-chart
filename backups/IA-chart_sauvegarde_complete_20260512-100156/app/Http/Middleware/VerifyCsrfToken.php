<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    // Ce tableau permettrait d exclure certaines routes du controle CSRF si necessaire.
    protected $except = [
        //
    ];
}
