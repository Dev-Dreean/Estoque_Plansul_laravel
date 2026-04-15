<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('is_impersonating') === true) {
            return $next($request);
        }

        /** @var User|null $user */
        $user = Auth::user();

        if ($user) {
            $needsProfile = $user->needsUf() || $user->needsIdentityUpdate() || $user->needsEmail();
            $needsPassword = ($user->must_change_password ?? false) || ($user->password_policy_version ?? 0) < 1;

            if (($needsProfile || $needsPassword) && !$request->routeIs('profile.completion.*')) {
                return redirect()
                    ->route('profile.completion.create')
                    ->with(
                        'warning',
                        $needsPassword
                            ? 'Defina uma nova senha e complete seu perfil antes de continuar.'
                            : 'Por favor, complete seu perfil antes de continuar.',
                    );
            }
        }

        return $next($request);
    }
}
