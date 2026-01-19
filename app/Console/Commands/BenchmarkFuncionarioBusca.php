<?php

namespace App\Console\Commands;

use App\Models\Funcionario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkFuncionarioBusca extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:funcionario-busca {termo?}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = '⚡ Benchmark de busca de funcionários (testa performance)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 BENCHMARK DE BUSCA DE FUNCIONÁRIOS');
        $this->line('═' . str_repeat('═', 78));

        // Termos para teste
        $termosPadrao = [
            '185' => 'Matrícula por prefixo',
            '1851' => 'Matrícula prefixo longo',
            'AARAN' => 'Nome por prefixo',
            'SILVA' => 'Nome comum',
            'JOÃO' => 'Nome com acento',
        ];

        $termo = $this->argument('termo');
        if ($termo) {
            $termosPadrao = [$termo => 'Termo fornecido'];
        }

        foreach ($termosPadrao as $busca => $descricao) {
            $this->line("\n📊 Testando: {$descricao}");
            $this->line("   Termo: '{$busca}'");
            $this->line('   ' . str_repeat('─', 74));

            // 1️⃣ TESTE NOVO (Query no banco)
            $this->testarBuscaOtimizada($busca);

            $this->line('');
        }

        $this->info('✅ Benchmark concluído!');
    }

    private function testarBuscaOtimizada(string $termo)
    {
        $isNumero = is_numeric($termo);
        
        $inicio = microtime(true);
        
        if ($isNumero) {
            // 🏃 MATRÍCULA: Prefixo match (ULTRA RÁPIDO)
            $resultado = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                ->where('CDMATRFUNCIONARIO', 'LIKE', $termo . '%')
                ->limit(15)
                ->get();
        } else {
            // 🔤 NOME: Busca FULLTEXT (ULTRA RÁPIDO)
            $resultado = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                ->whereRaw('MATCH(NMFUNCIONARIO) AGAINST(? IN NATURAL LANGUAGE MODE)', [$termo])
                ->orderByRaw('MATCH(NMFUNCIONARIO) AGAINST(?)', [$termo], 'desc')
                ->limit(15)
                ->get();
            
            // Fallback: se FULLTEXT retornar vazio
            if ($resultado->isEmpty()) {
                $resultado = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                    ->whereRaw('UPPER(NMFUNCIONARIO) LIKE ?', [strtoupper($termo) . '%'])
                    ->limit(15)
                    ->get();
            }
        }

        $tempo = (microtime(true) - $inicio) * 1000; // em ms
        
        // Converter Collection para array para manipulação
        $resultadoArray = $resultado->toArray();
        $count = count($resultadoArray);
        $label = $isNumero ? '🔢 Matrícula' : '🔤 Nome (FULLTEXT)';
        
        // Formatar tempo com cor
        if ($tempo < 10) {
            $tempoFormatado = sprintf("<fg=green;options=bold>%.2fms</> ✨ (ULTRA RÁPIDO)", $tempo);
        } elseif ($tempo < 50) {
            $tempoFormatado = sprintf("<fg=green>%.2fms</> ✅ (Rápido)", $tempo);
        } elseif ($tempo < 100) {
            $tempoFormatado = sprintf("<fg=yellow>%.2fms</> ⚠️  (Aceitável)", $tempo);
        } else {
            $tempoFormatado = sprintf("<fg=red>%.2fms</> ❌ (Lento)", $tempo);
        }

        $this->line("   {$label} Busca Otimizada:");
        $this->line("   └─ Tempo: {$tempoFormatado}");
        $this->line("   └─ Resultados: <fg=blue;options=bold>{$count}</> registros encontrados");
        
        if ($count > 0) {
            $this->line('   └─ Primeiros 3:');
            foreach (array_slice($resultadoArray, 0, 3) as $func) {
                $nome = substr($func['NMFUNCIONARIO'], 0, 40);
                $this->line("      • {$func['CDMATRFUNCIONARIO']} - {$nome}");
            }
        }
    }
}
