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
     * Controle de acesso por tela baseado em acessotela + acessousuario.
     * - ADM: acesso total
     * - Demais perfis: acesso apenas se houver vínculo ativo
     */
    public function handle(Request $request, Closure $next, ?int $nuseqtela = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Você precisa estar autenticado para acessar esta página.');
        }

        /** @var User|null $user */
        $user = Auth::user();

        // Administrador tem acesso a tudo
        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($nuseqtela !== null && !$user->temAcessoTela($nuseqtela)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Você não tem permissão para acessar esta funcionalidade.',
                    'code' => 'access_denied',
                ], 403);
            }

            return response()->view('errors.403', [], 403);
        }

        return $next($request);
    }
}

