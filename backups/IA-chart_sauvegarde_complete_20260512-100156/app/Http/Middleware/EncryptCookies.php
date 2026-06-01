<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    // Ce tableau peut contenir les cookies a laisser non chiffres si un besoin particulier apparait.
    protected $except = [
        //
    ];
}
