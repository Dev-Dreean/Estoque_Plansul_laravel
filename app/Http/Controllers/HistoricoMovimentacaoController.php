<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovimentacao;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoricoMovimentacaoController extends Controller
{
    public function index(Request $request): View
    {
        $query = HistoricoMovimentacao::query();

        if ($request->filled('nupatr')) {
            $query->where('NUPATRIMONIO', $request->nupatr);
        }
        if ($request->filled('codproj')) {
            $query->where('CDPROJETO', $request->codproj);
        }
        if ($request->filled('usuario')) {
            $query->where('USUARIO', 'like', '%' . $request->usuario . '%');
        }
        if ($request->filled('de') && $request->filled('ate')) {
            $query->whereBetween('DTOPERACAO', [$request->de, $request->ate]);
        } elseif ($request->filled('de')) {
            $query->where('DTOPERACAO', '>=', $request->de);
        } elseif ($request->filled('ate')) {
            $query->where('DTOPERACAO', '<=', $request->ate);
        }

        $query->orderByDesc('DTOPERACAO');

        $perPage = (int) $request->input('per_page', 30);
        $perPage = max(10, min(200, $perPage));

        $historico = $query->paginate($perPage)->withQueryString();

        return view('relatorios.historico', compact('historico'));
    }
}
