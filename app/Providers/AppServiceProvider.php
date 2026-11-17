<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // On the live server every link and form must use HTTPS (patient data in transit).
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
