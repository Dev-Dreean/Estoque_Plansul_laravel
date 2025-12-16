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
     * Controle de acesso simplificado por perfil
     * - C: acesso apenas a tela 1000 (Patrimônio)
     * - USR: acesso apenas a telas 1000 (Patrimônio), 1001 (Gráficos), 1006 (Relatórios) e 1007 (Histórico)
     * - ADM: acesso a tudo
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

        // Consultor (C) só pode acessar tela 1000 (Patrimônio)
        if ($user->PERFIL === User::PERFIL_CONSULTOR) {
            $telasPermitidas = [1000];

            if ($nuseqtela !== null && !in_array($nuseqtela, $telasPermitidas, true)) {
                // Permite telas adicionais via permissões no banco (acessousuario + acessotela)
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Você não tem permissão para acessar esta funcionalidade.',
                        'code' => 'access_denied',
                    ], 403);
                }

                return redirect()->route('patrimonios.index')
                    ->with('error', 'Você não tem permissão para acessar esta página.');
            }
        }

        // Usuário comum (USR) só pode acessar telas 1000, 1001, 1006 e 1007
        if ($user->PERFIL === User::PERFIL_USUARIO) {
            $telasPermitidas = [1000, 1001, 1006, 1007];
            
            if ($nuseqtela !== null && !in_array($nuseqtela, $telasPermitidas, true)) {
                // Permite telas adicionais via permissões no banco (acessousuario + acessotela)
                if ($user->temAcessoTela($nuseqtela)) {
                    return $next($request);
                }

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
        }

        return $next($request);
    }
}
