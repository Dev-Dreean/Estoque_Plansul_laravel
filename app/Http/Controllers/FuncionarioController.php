<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;

class FuncionarioController extends Controller
{
    /**
     * Pesquisa funcionarios por termo (código ou nome) retornando até 10 resultados.
     */
    public function pesquisar(Request $request)
    {
        $termo = trim($request->input('q', ''));
        if ($termo === '') {
            return response()->json([]);
        }

        $palavras = array_filter(explode(' ', $termo), fn($p) => $p !== '');

        $query = Funcionario::query();

        // Agrupa as palavras exigindo TODAS no nome
        if (!empty($palavras)) {
            $query->where(function ($q) use ($palavras) {
                foreach ($palavras as $palavra) {
                    $q->where('NMFUNCIONARIO', 'like', '%' . $palavra . '%');
                }
            });
        }

        // Se termo é numérico, adiciona OR para matrícula exata (mantendo bloco anterior)
        if (is_numeric($termo)) {
            $query->orWhere('CDMATRFUNCIONARIO', $termo);
        }

        // Ranking de relevância: 1 = matrícula exata, 2 = nome inicia com termo, 3 = demais
        $query->orderByRaw(
            "CASE \n" .
                " WHEN CDMATRFUNCIONARIO = ? THEN 1 \n" .
                " WHEN NMFUNCIONARIO LIKE ? THEN 2 \n" .
                " ELSE 3 END",
            [$termo, $termo . '%']
        );

        $funcionarios = $query
            ->select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
            ->limit(10)
            ->get();

        return response()->json($funcionarios);
    }
}
