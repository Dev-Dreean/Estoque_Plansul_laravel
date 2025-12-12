<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckConsultaUser
{
    /**
     * Bloqueia usuários com role 'consulta' de fazer operações de escrita
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->isConsulta()) {
            // Permitir: GET (consulta), HEAD, OPTIONS
            if (!in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
                Log::warning('❌ Usuário consulta tentou operação de escrita', [
                    'user' => $user->NMLOGIN,
                    'method' => $request->getMethod(),
                    'path' => $request->path(),
                ]);
                
                return redirect()->route('patrimonios.index')
                    ->with('error', '❌ Você não tem permissão para modificar dados.');
            }
        }

        return $next($request);
    }
}
