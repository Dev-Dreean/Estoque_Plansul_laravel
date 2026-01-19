<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BenchmarkController extends Controller
{
    public function test()
    {
        $resultado = [
            'servidor' => 'kinghost',
            'timestamp' => date('Y-m-d H:i:s'),
            'testes' => []
        ];

        // Teste 1: Contar funcionários
        $inicio = microtime(true);
        $totalFuncionarios = DB::table('funcionarios')->count();
        $tempo1 = (microtime(true) - $inicio) * 1000;

        $resultado['testes']['total_funcionarios'] = [
            'quantidade' => $totalFuncionarios,
            'tempo_ms' => round($tempo1, 2)
        ];

        // Teste 2: Busca por matrícula
        $inicio = microtime(true);
        $result = DB::table('funcionarios')
            ->where('CDMATRFUNCIONARIO', '188252')
            ->first();
        $tempo2 = (microtime(true) - $inicio) * 1000;

        $resultado['testes']['busca_matricula'] = [
            'matricula' => '188252',
            'encontrado' => $result ? true : false,
            'tempo_ms' => round($tempo2, 2)
        ];

        // Teste 3: Busca LIKE por nome
        $inicio = microtime(true);
        $count = DB::table('funcionarios')
            ->where('NMFUNCIONARIO', 'LIKE', 'ABIGA%')
            ->count();
        $tempo3 = (microtime(true) - $inicio) * 1000;

        $resultado['testes']['busca_nome_like'] = [
            'busca' => 'ABIGA%',
            'quantidade_resultados' => $count,
            'tempo_ms' => round($tempo3, 2)
        ];

        // Teste 4: Streaming de 5000 registros (simular relatório)
        $inicio = microtime(true);
        $count = 0;
        $query = DB::table('funcionarios')
            ->select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO', 'NMCARGO', 'DESUF'])
            ->limit(5000);
        
        foreach ($query->cursor() as $record) {
            $count++;
        }
        $tempo4 = (microtime(true) - $inicio) * 1000;

        $resultado['testes']['streaming_5k'] = [
            'registros' => $count,
            'tempo_ms' => round($tempo4, 2),
            'velocidade_ms_por_mil' => round($tempo4 / 5, 2)
        ];

        // Teste 5: Índices
        $indices = DB::select("SHOW INDEXES FROM funcionarios");
        $resultado['testes']['indices'] = array_map(function ($idx) {
            return [
                'coluna' => $idx->Column_name,
                'tipo' => $idx->Index_type,
                'cardinalidade' => $idx->Cardinality ?? 0
            ];
        }, $indices);

        // Resumo
        $resultado['resumo'] = [
            'velocidade_total_ms' => round($tempo1 + $tempo2 + $tempo3 + $tempo4, 2),
            'status' => $tempo4 > 10000 ? '❌ MUITO LENTO' : ($tempo4 > 5000 ? '⚠️ LENTO' : '✅ OK')
        ];

        return response()->json($resultado, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
