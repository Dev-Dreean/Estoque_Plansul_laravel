<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CheckTelaAccess
{
    /**
     * Verifica se o usuário tem acesso à tela
     * Uso: Route::get('/patrimonios', ...)->middleware('tela.access:1000');
     */
    public function handle(Request $request, Closure $next, ?int $nuseqtela = null): Response
    {
        if ($nuseqtela === null) {
            return $next($request);
        }

        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Você precisa estar autenticado para acessar esta página.');
        }

        /** @var User|null $user */
        $user = Auth::user();

        // Super Admin tem acesso total
        if ($user->isGod()) {
            return $next($request);
        }

        if (!$user->temAcessoTela((int)$nuseqtela)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar esta funcionalidade.',
                    'code' => 'access_denied',
                ], 403);
            }

            // Para requisições normais, redireciona para dashboard com mensagem
            return redirect()->route('dashboard')
                ->with('error', 'Você não tem permissão para acessar esta página.');
        }

        return $next($request);
    }
}
