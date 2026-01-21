<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Permitir acesso apenas para Admin (ADM)
        if ($user && $user->isAdmin()) {
            return $next($request); // Acesso permitido, pode passar.
        }
        // Se não for admin, nega o acesso.
        abort(403, 'Acesso não autorizado.');
    }
}

