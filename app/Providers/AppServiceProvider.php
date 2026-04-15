<?php

namespace App\Providers;

use App\Models\LocalProjeto;
use App\Models\ObjetoPatr;
use App\Models\Patrimonio;
use App\Models\User;
use App\Observers\PatrimonioObserver;
use App\Observers\RegistroRemovidoObserver;
use App\Services\ImportantNotificationsService;
use App\Services\SystemNewsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImportantNotificationsService::class);
        $this->app->singleton(SystemNewsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ADICIONE ESTA LINHA
        Schema::defaultStringLength(191);

        // 🔧 Hotfix: MySQL 5.6 no KingHost não tem "generation_expression"
        // Isso evita errors ao acessar information_schema
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=''");
        }

        Paginator::defaultView('custom.pagination-pt');
        Paginator::defaultSimpleView('custom.pagination-pt');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Patrimonio::observe(RegistroRemovidoObserver::class);
        Patrimonio::observe(PatrimonioObserver::class);
        LocalProjeto::observe(RegistroRemovidoObserver::class);
        ObjetoPatr::observe(RegistroRemovidoObserver::class);
        User::observe(RegistroRemovidoObserver::class);

        View::composer('layouts.navigation', function ($view) {
            $user = Auth::user();
            $payload = [
                'items' => [],
                'grouped' => [],
                'total_count' => 0,
                'generated_at' => now()->toIso8601String(),
            ];

            if ($user) {
                $payload = app(ImportantNotificationsService::class)->payloadForUser($user);
            }

            $view->with('importantNotificationsPayload', $payload);
        });

        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $payload = [
                'items' => [],
                'unseen_keys' => [],
                'unseen_count' => 0,
                'should_auto_open' => false,
                'generated_at' => now()->toIso8601String(),
            ];

            if ($user) {
                $payload = app(SystemNewsService::class)->payloadForUser($user);
            }

            $view->with('systemNewsPayload', $payload);
        });
    }
}
