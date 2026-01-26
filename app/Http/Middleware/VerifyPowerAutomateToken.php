<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyPowerAutomateToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('solicitacoes_bens.power_automate_token', '');
        $provided = (string) $request->header('X-API-KEY', '');

        if ($expected === '') {
            Log::warning('Power Automate token not configured for solicitacoes email endpoint.');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if (!hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
