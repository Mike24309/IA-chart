<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    // Cette methode active les routes de diffusion d evenements temps reel.
    public function boot(): void
    {
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
