<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovimentacao;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HistoricoController extends Controller
{
    public function index(Request $request): View
    {
        $query = HistoricoMovimentacao::query()
            // Vincula patrimônio para aplicar mesma regra de visibilidade (responsável OU criador)
            ->leftJoin('patr as p', 'p.NUPATRIMONIO', '=', 'movpartr.NUPATR')
            ->leftJoin('usuario as u1', function ($join) {
                $join->on(
                    DB::raw("CONVERT(u1.NMLOGIN USING utf8mb4) COLLATE utf8mb4_unicode_ci"),
                    '=',
                    DB::raw("CONVERT(movpartr.USUARIO USING utf8mb4) COLLATE utf8mb4_unicode_ci")
                );
            })
            ->leftJoin('usuario as u2', function ($join) {
                $join->on(
                    DB::raw("CONVERT(u2.NMLOGIN USING utf8mb4) COLLATE utf8mb4_unicode_ci"),
                    '=',
                    DB::raw("CONVERT(movpartr.CO_AUTOR USING utf8mb4) COLLATE utf8mb4_unicode_ci")
                );
            })
            ->select(
                'movpartr.*',
                'u1.CDMATRFUNCIONARIO as MAT_USUARIO',
                'u2.CDMATRFUNCIONARIO as MAT_CO_AUTOR',
                'u1.NOMEUSER as NM_USUARIO',
                'u2.NOMEUSER as NM_CO_AUTOR'
            );

        // Segurança: usuários não-ADM veem históricos que:
        // (a) Foram feitos por eles (USUARIO = NMLOGIN) OU
        // (b) Pertencem a patrimônios que eles podem ver (responsável OU criador do patrimônio)
        $user = Auth::user();
        if ($user && ($user->PERFIL ?? null) !== 'ADM') {
            $nmLogin = trim((string)($user->NMLOGIN ?? ''));
            $nmUser  = trim((string)($user->NOMEUSER ?? ''));
            $mat     = (string)($user->CDMATRFUNCIONARIO ?? '');

            $query->where(function ($q) use ($nmLogin, $nmUser, $mat) {
                // (a) Ações feitas pelo usuário
                $q->whereRaw('LOWER(movpartr.USUARIO) = LOWER(?)', [$nmLogin])
                    // (b) Movimentações de patrimônios visíveis ao usuário
                    ->orWhere(function ($q2) use ($nmLogin, $nmUser, $mat) {
                        $q2->where('p.CDMATRFUNCIONARIO', $mat)
                            ->orWhereRaw('LOWER(p.USUARIO) = LOWER(?)', [$nmLogin])
                            ->orWhereRaw('LOWER(p.USUARIO) = LOWER(?)', [$nmUser]);
                    });
            });
        }

        if ($request->filled('nupatr')) {
            $query->where('NUPATR', $request->nupatr);
        }
        if ($request->filled('codproj')) {
            $query->where('CODPROJ', $request->codproj);
        }
        // Filtro por usuário só é respeitado para administradores
        if ($request->filled('usuario') && $user && ($user->PERFIL ?? null) === 'ADM') {
            $query->where('movpartr.USUARIO', 'like', '%' . $request->usuario . '%');
        }
        if ($request->filled('tipo')) {
            $query->where('TIPO', $request->tipo);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('DTOPERACAO', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('DTOPERACAO', '<=', $request->data_fim);
        }

        $query->orderBy('movpartr.DTOPERACAO', 'desc');

        $perPage = (int) $request->input('per_page', 30);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 200) $perPage = 200;

        $historicos = $query->paginate($perPage)->withQueryString();
        $usuarios = HistoricoMovimentacao::select('USUARIO')->whereNotNull('USUARIO')->distinct()->orderBy('USUARIO')->pluck('USUARIO');

        return view('historico.index', compact('historicos', 'usuarios'));
    }
}
