<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se o usuário está autenticado e tem remember_device ativo
        if (Auth::check() && $request->session()->has('remember_device')) {
            $loginTimestamp = $request->session()->get('login_timestamp');
            
            // Verificar se passaram 7 dias (604800 segundos)
            if ($loginTimestamp && (now()->timestamp - $loginTimestamp) > 604800) {
                // Fazer logout
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirecionar para menu com mensagem
                return redirect()->route('menu.index')->with('message', 'Sua sessão expirou após 7 dias. Por favor, faça login novamente.');
            }
        }
        
        return $next($request);
    }
}

