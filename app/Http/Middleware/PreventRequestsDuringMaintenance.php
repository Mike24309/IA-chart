<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    // Ce tableau peut contenir les routes autorisees meme pendant le mode maintenance.
    protected $except = [
        //
    ];
}
