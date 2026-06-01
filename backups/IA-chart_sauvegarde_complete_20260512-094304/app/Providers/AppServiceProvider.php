<?php

namespace App\Providers;

use App\Services\ParameterService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('appSettings', app(ParameterService::class)->current());
        });
    }
}
