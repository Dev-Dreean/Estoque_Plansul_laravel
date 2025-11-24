<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest; // << aqui

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'tela.access' => \App\Http\Middleware\CheckTelaAccess::class,
            'can.delete' => \App\Http\Middleware\CheckDeletePermission::class,
            'session.expiration' => \App\Http\Middleware\CheckSessionExpiration::class,
        ]);

        // Adicionar middleware global para verificar expiração da sessão
        $middleware->append(\App\Http\Middleware\CheckSessionExpiration::class);

        // Exceções de CSRF para rotas API
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Confiar nos proxies da hospedagem (KingHost)
        $middleware->trustProxies(
            at: '*',
            headers: SymfonyRequest::HEADER_X_FORWARDED_FOR  |
                SymfonyRequest::HEADER_X_FORWARDED_HOST |
                SymfonyRequest::HEADER_X_FORWARDED_PORT |
                SymfonyRequest::HEADER_X_FORWARDED_PROTO
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
