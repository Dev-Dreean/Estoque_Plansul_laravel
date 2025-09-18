<?php

namespace App\Providers;

// ADICIONE ESTA LINHA
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ADICIONE ESTA LINHA
        Schema::defaultStringLength(191);

        Paginator::defaultView('custom.pagination-pt');
        Paginator::defaultSimpleView('custom.pagination-pt');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
