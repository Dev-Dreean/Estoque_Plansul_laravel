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
        $user = Auth::user();

        if ($user) {
            $needsProfile = is_null($user->UF);
            $needsPassword = (property_exists($user, 'must_change_password') && $user->must_change_password) || ($user->password_policy_version ?? 0) < 1;
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
