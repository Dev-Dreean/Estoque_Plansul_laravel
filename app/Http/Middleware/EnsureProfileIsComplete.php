<?php
declare(strict_types=1);
// DENTRO DE app/Http/Middleware/EnsureProfileIsComplete.php

namespace App\Http\Middleware; // <-- Verifique se o namespace está correto

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete // <-- Verifique se o nome da classe está correto
{
    public function handle(Request $request, Closure $next): Response
    {
        // If we are impersonating another user for testing, skip the profile completion
        if (session('is_impersonating') === true) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user) {
            $needsProfile = is_null($user->UF);
            // Eloquent attributes must be checked via null-coalescing; property_exists doesn't work for dynamic attributes
            $needsPassword = ($user->must_change_password ?? false) || ($user->password_policy_version ?? 0) < 1;
            if (($needsProfile || $needsPassword) && !$request->routeIs('profile.completion.*')) {
                return redirect()->route('profile.completion.create')
                    ->with(
                        'warning',
                        $needsPassword
                            ? 'Defina uma nova senha e complete seu perfil antes de continuar.'
                            : 'Por favor, complete seu perfil antes de continuar.'
                    );
            }
        }

        return $next($request);
    }
}
