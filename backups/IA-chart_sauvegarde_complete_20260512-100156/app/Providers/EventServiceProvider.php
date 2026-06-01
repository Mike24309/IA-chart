<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    // Ce tableau relie les evenements Laravel aux listeners qui doivent reagir automatiquement.
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    // Cette methode permettrait d enregistrer d autres evenements applicatifs si besoin.
    public function boot(): void
    {
        //
    }

    // Cette methode indique si Laravel doit chercher automatiquement des evenements et listeners.
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
