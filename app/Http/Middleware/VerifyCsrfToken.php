<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // API routes - GET requests don't mutate data
        'api/projetos/*',
        'api/locais/*',
        'api/objetos/*',
        'api/responsaveis/*',
        'api/matriculas/*',
        'api/weather*',
    ];

    /**
     * Handle the request.
     */
    public function handle($request, \Closure $next)
    {
        // For JSON requests with X-CSRF-TOKEN header (fetch API from modals),
        // Laravel's CSRF verification checks multiple places:
        // 1. _token in POST data
        // 2. X-CSRF-TOKEN header
        // 3. X-XSRF-TOKEN header
        // The X-CSRF-TOKEN header should be checked by default, but let's ensure it works
        
        // If it's a JSON request with X-CSRF-TOKEN, it should work with parent::handle()
        return parent::handle($request, $next);
    }
}

