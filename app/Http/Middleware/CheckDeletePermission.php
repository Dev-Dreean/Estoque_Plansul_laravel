<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckDeletePermission
{
    /**
     * Verifica se o usuário pode excluir registros
     * Super Admin tem permissão total
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Você precisa estar autenticado.');
        }

        /** @var User $user */
        $user = Auth::user();

        // Super Admin bypassa verificações
        if ($user->isGod()) {
            return $next($request);
        }

        // Verifica se é requisição DELETE
        $isDeleteRequest = $request->isMethod('delete') ||
            $request->route()->getActionMethod() === 'destroy';

        if ($isDeleteRequest && !$user->podeExcluir()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Apenas Super Administradores podem excluir registros.',
                    'code' => 'delete_permission_denied',
                ], 403);
            }
            return redirect()->back()
                ->with('error', 'Apenas Super Administradores podem excluir registros.');
        }

        return $next($request);
    }
}

