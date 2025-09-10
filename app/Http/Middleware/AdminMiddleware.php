<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->PERFIL === 'ADM') {
            return $next($request); // Acesso permitido, pode passar.
        }
        // Se não for admin, nega o acesso.
        abort(403, 'Acesso não autorizado.'); 
    }
}