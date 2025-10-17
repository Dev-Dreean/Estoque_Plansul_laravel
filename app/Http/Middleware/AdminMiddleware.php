<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Permitir acesso para Admin (ADM) e Super Admin (SUP)
        if (Auth::check() && in_array(Auth::user()->PERFIL, ['ADM', 'SUP'])) {
            return $next($request); // Acesso permitido, pode passar.
        }
        // Se não for admin ou super admin, nega o acesso.
        abort(403, 'Acesso não autorizado.');
    }
}
