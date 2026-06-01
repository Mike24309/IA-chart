<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    // Ce tableau peut servir a relier des modeles a des policies d autorisation.
    protected $policies = [
        //
    ];

    // Cette methode permettrait d enregistrer des regles d autorisation supplementaires.
    public function boot(): void
    {
        //
    }
}
