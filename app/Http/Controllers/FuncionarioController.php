<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use App\Services\FilterService;

class FuncionarioController extends Controller
{
    /**
     * Pesquisa funcionarios por termo (código ou nome) com sistema inteligente de filtros
     */
    public function pesquisar(Request $request)
    {
        $termo = trim($request->input('q', ''));

        // Buscar todos os funcionários
        $funcionarios = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
            ->get()
            ->toArray();

        // Aplicar filtro inteligente
        $filtrados = FilterService::filtrar(
            $funcionarios,
            $termo,
            ['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'],  // campos de busca
            ['CDMATRFUNCIONARIO' => 'número', 'NMFUNCIONARIO' => 'texto'],  // tipos de campo
            10  // limite
        );

        return response()->json($filtrados);
    }
}
