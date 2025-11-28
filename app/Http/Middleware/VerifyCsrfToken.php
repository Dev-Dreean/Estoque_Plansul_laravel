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
        //
    ];

    /**
     * Handle the request.
     */
    public function handle($request, \Closure $next)
    {
        // For JSON requests with X-CSRF-TOKEN header (fetch API from modals),
        // ensure the token is available in the expected format
        if (($request->isJson() || $request->expectsJson()) && $request->hasHeader('X-CSRF-TOKEN')) {
            // Set the _token field for CSRF verification
            $request->merge(['_token' => $request->header('X-CSRF-TOKEN')]);
        }

        return parent::handle($request, $next);
    }
}
