<?php

namespace App\Providers;

// USE ESTAS DUAS LINHAS PARA IMPORTAR OS MODELS NECESSÁRIOS
use App\Models\Patrimonio;
use App\Policies\PatrimonioPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // ADICIONE ESTA LINHA PARA CONECTAR O MODEL À POLICY
        Patrimonio::class => PatrimonioPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}