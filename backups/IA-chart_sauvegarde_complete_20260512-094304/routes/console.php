<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Routes console
|--------------------------------------------------------------------------
|
| Ce fichier permet de declarer des commandes Artisan simples basees
| sur des closures, utiles pour des taches internes ou de maintenance.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
