<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Funcionario;

class RelatorioDownloadController extends Controller
{
    /**
     * 🚀 Gera relatório em tempo real com streaming
     * Retorna arquivo CSV direto sem guardar em disco
     */
    public function download()
    {
        Log::info("📋 [RELATORIO_STREAM] Iniciando geração com streaming...");
        
        $inicio = microtime(true);
        $count = 0;

        // Headers para download direto
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="relatorio_funcionarios.csv"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];

        $callback = function () use (&$count) {
            $file = fopen('php://output', 'w');
            
            // Header do CSV
            fputcsv($file, ['Matrícula', 'Nome', 'Cargo', 'Filial', 'UF', 'Data Admissão'], ';');
            fflush($file);

            // Stream dos registros
            Funcionario::select([
                'CDMATRFUNCIONARIO',
                'NMFUNCIONARIO',
                'CDCARGO',
                'CODFIL',
                'UFPROJ',
                'DTADMISSAO'
            ])
            ->orderBy('CDMATRFUNCIONARIO')
            ->cursor()
            ->each(function ($func) use ($file, &$count) {
                fputcsv($file, [
                    $func->CDMATRFUNCIONARIO,
                    $func->NMFUNCIONARIO,
                    $func->CDCARGO ?? '-',
                    $func->CODFIL ?? '-',
                    $func->UFPROJ ?? '-',
                    $func->DTADMISSAO ?? '-',
                ], ';');
                
                $count++;
                
                // Flush a cada 1000 registros para não pesar memória
                if ($count % 1000 == 0) {
                    fflush($file);
                }
            });

            fclose($file);
        };

        $tempo = microtime(true) - $inicio;
        Log::info("✅ [RELATORIO_STREAM] Relatório enviado", [
            'registros' => $count,
            'tempo_segundos' => number_format($tempo, 2)
        ]);

        return response()->stream($callback, 200, $headers);
    }
}


