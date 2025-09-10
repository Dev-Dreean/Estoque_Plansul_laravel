<?php

namespace App\Http\Controllers;

// Classes do Laravel e Modelos
use App\Models\Patrimonio;
use App\Models\User;
use App\Models\Objpatr;
use App\Models\Tabfant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class PatrimonioController extends Controller
{
    // ... (index, create, store, edit, update, destroy)

    public function index(Request $request): View
    {
        // ... seu código do index ...
    }

    public function create(): View
    {
        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();
        return view('patrimonios.create', compact('projetos'));
    }

    // ... e assim por diante para todos os seus métodos CRUD ...

    // --- MÉTODOS DE API PARA O FORMULÁRIO DINÂMICO ---

    public function buscarPorNumero($numero): JsonResponse
    {
        $patrimonio = Patrimonio::where('NUPATRIMONIO', $numero)->first();
        if ($patrimonio) { return response()->json($patrimonio); }
        return response()->json(null, 404);
    }
    
    public function pesquisar(Request $request): JsonResponse
    {
        $termo = $request->input('q', '');
        $patrimonios = Patrimonio::query()->where('DEPATRIMONIO', 'like', "%{$termo}%")->orWhere('NUPATRIMONIO', 'like', "%{$termo}%")->select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO'])->limit(10)->get();
        return response()->json($patrimonios);
    }
    
    public function buscarCodigoObjeto($codigo): JsonResponse
    {
        $objeto = Objpatr::where('NUSEQOBJ', $codigo)->first();
        if ($objeto) { 
            return response()->json(['DEPATRIMONIO' => $objeto->DEOBJETO]); 
        }
        return response()->json(['DEPATRIMONIO' => 'Código não encontrado.'], 404);
    }

    public function buscarProjeto($cdprojeto): JsonResponse
    {
        $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first(['NOMEPROJETO']);
        return response()->json($projeto);
    }

    public function getLocaisPorProjeto($cdprojeto): JsonResponse
    {
        $locais = Tabfant::where('CDPROJETO', $cdprojeto)->distinct()->pluck('LOCAL');
        return response()->json($locais);
    }
    
}