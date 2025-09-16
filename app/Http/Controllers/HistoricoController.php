<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovimentacao;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoricoController extends Controller
{
    public function index(Request $request): View
    {
        $query = HistoricoMovimentacao::query();

        if ($request->filled('nupatr')) {
            $query->where('NUPATR', $request->nupatr);
        }
        if ($request->filled('codproj')) {
            $query->where('CODPROJ', $request->codproj);
        }
        if ($request->filled('usuario')) {
            $query->where('USUARIO', 'like', '%' . $request->usuario . '%');
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

        $query->orderBy('DTOPERACAO', 'desc');

        $perPage = (int) $request->input('per_page', 30);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 200) $perPage = 200;

        $historicos = $query->paginate($perPage)->withQueryString();
        $usuarios = HistoricoMovimentacao::select('USUARIO')->whereNotNull('USUARIO')->distinct()->orderBy('USUARIO')->pluck('USUARIO');

        return view('historico.index', compact('historicos', 'usuarios'));
    }
}
