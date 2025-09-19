<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovimentacao;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class HistoricoController extends Controller
{
    public function index(Request $request): View
    {
        $query = HistoricoMovimentacao::query()
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

        if ($request->filled('nupatr')) {
            $query->where('NUPATR', $request->nupatr);
        }
        if ($request->filled('codproj')) {
            $query->where('CODPROJ', $request->codproj);
        }
        if ($request->filled('usuario')) {
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
