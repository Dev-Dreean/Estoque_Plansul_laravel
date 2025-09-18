<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // suas views de paginação
        Paginator::defaultView('custom.pagination-pt');
        Paginator::defaultSimpleView('custom.pagination-pt');

        // força HTTPS em produção
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        // Removido o view composer para voltar ao modelo anterior
    }
}
