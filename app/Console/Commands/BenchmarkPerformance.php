<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BenchmarkPerformance extends Command
{
    protected $signature = 'benchmark:performance';
    protected $description = 'Teste de velocidade completo do sistema (relatório, buscas, índices)';

    public function handle()
    {
        $this->info("\n╔════════════════════════════════════════════════════════════════════════════════╗");
        $this->info("║                    🚀 BENCHMARK KINGHOST - TESTE DE VELOCIDADE                 ║");
        $this->info("╚════════════════════════════════════════════════════════════════════════════════╝\n");

        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'testes' => []
        ];

        // ============================================================================
        // 1️⃣ TESTE DE RELATÓRIO (Simular geração do CSV)
        // ============================================================================
        $this->info("📊 1️⃣ TESTE DE GERAÇÃO DO RELATÓRIO (92,755 registros)");
        $this->line(str_repeat("─", 80));

        try {
            $query = DB::table('funcionarios')
                ->select([
                    'CDMATRFUNCIONARIO',
                    'NMFUNCIONARIO',
                    'NMCARGO',
                    'DESUF',
                    'NMFILIAL',
                    'TPFUNCIONARIO'
                ])
                ->orderBy('CDMATRFUNCIONARIO', 'asc');
            
            $this->info("⏱️  Contando registros totais...");
            $contagem = $query->count();
            $this->info("   Total: {$contagem} registros\n");
            
            $this->info("⏱️  Iterando com cursor...");
            $inicio = microtime(true);
            
            $totalRecords = 0;
            DB::cursor($query)->each(function ($record) use (&$totalRecords) {
                $totalRecords++;
                if ($totalRecords % 10000 == 0) {
                    $this->info("   ✓ {$totalRecords} registros processados");
                }
            });
            
            $tempoRelatorio = microtime(true) - $inicio;
            
            $this->info("\n✅ Relatório gerado em " . number_format($tempoRelatorio, 2) . "s");
            $this->info("   Velocidade: " . number_format($contagem / $tempoRelatorio, 0) . " registros/segundo");
            
            $results['testes']['relatorio'] = [
                'tempo_segundos' => round($tempoRelatorio, 2),
                'registros' => $contagem,
                'velocidade_por_seg' => round($contagem / $tempoRelatorio, 2)
            ];
            
        } catch (\Exception $e) {
            $this->error("❌ ERRO: " . $e->getMessage());
            $results['testes']['relatorio'] = ['erro' => $e->getMessage()];
        }

        // ============================================================================
        // 2️⃣ TESTE DE BUSCA POR MATRÍCULA
        // ============================================================================
        $this->line("\n\n🔍 2️⃣ TESTE DE BUSCA POR MATRÍCULA");
        $this->line(str_repeat("─", 80));

        $buscasMatricula = ['188252', '20', '999989', '456789', '123456'];
        $temposMatricula = [];

        foreach ($buscasMatricula as $matricula) {
            $inicio = microtime(true);
            
            $resultado = DB::table('funcionarios')
                ->where('CDMATRFUNCIONARIO', '=', $matricula)
                ->first();
            
            $tempo = (microtime(true) - $inicio) * 1000;
            $temposMatricula[] = $tempo;
            
            $status = $resultado ? '✅ ENCONTRADO' : '⚠️  NÃO ENCONTRADO';
            $this->info("Busca CDMATRFUNCIONARIO={$matricula}: {$tempo}ms {$status}");
        }

        $mediaMatricula = array_sum($temposMatricula) / count($temposMatricula);
        $this->info("\n📈 Média: " . number_format($mediaMatricula, 2) . "ms");

        $results['testes']['busca_matricula'] = [
            'tempo_medio_ms' => round($mediaMatricula, 2),
            'tempos_individuais' => array_map(fn($t) => round($t, 2), $temposMatricula)
        ];

        // ============================================================================
        // 3️⃣ TESTE DE BUSCA POR NOME (LIKE)
        // ============================================================================
        $this->line("\n\n🔍 3️⃣ TESTE DE BUSCA POR NOME (LIKE)");
        $this->line(str_repeat("─", 80));

        $buscasNome = ['ABIGA', 'JO%', 'MAR%', 'SILVA', 'SANTOS'];
        $temposNome = [];

        foreach ($buscasNome as $nome) {
            $inicio = microtime(true);
            
            $resultado = DB::table('funcionarios')
                ->where('NMFUNCIONARIO', 'LIKE', "{$nome}%")
                ->count();
            
            $tempo = (microtime(true) - $inicio) * 1000;
            $temposNome[] = $tempo;
            
            $this->info("Busca LIKE '{$nome}%': {$tempo}ms ({$resultado} resultados)");
        }

        $mediaNome = array_sum($temposNome) / count($temposNome);
        $this->info("\n📈 Média: " . number_format($mediaNome, 2) . "ms");

        $results['testes']['busca_nome'] = [
            'tempo_medio_ms' => round($mediaNome, 2),
            'tempos_individuais' => array_map(fn($t) => round($t, 2), $temposNome)
        ];

        // ============================================================================
        // 4️⃣ TESTE DE BUSCA FULLTEXT
        // ============================================================================
        $this->line("\n\n🔍 4️⃣ TESTE DE BUSCA FULLTEXT");
        $this->line(str_repeat("─", 80));

        $buscasFulltext = ['ABIGAIL', 'JOAO', 'MARIA'];
        $temposFulltext = [];

        foreach ($buscasFulltext as $termo) {
            $inicio = microtime(true);
            
            $resultado = DB::table('funcionarios')
                ->whereRaw("MATCH(NMFUNCIONARIO) AGAINST(? IN BOOLEAN MODE)", ["+{$termo}*"])
                ->count();
            
            $tempo = (microtime(true) - $inicio) * 1000;
            $temposFulltext[] = $tempo;
            
            $this->info("FULLTEXT MATCH '{$termo}*': {$tempo}ms ({$resultado} resultados)");
        }

        $mediaFulltext = array_sum($temposFulltext) / count($temposFulltext);
        $this->info("\n📈 Média: " . number_format($mediaFulltext, 2) . "ms");

        $results['testes']['busca_fulltext'] = [
            'tempo_medio_ms' => round($mediaFulltext, 2),
            'tempos_individuais' => array_map(fn($t) => round($t, 2), $temposFulltext)
        ];

        // ============================================================================
        // 5️⃣ ANÁLISE DE ÍNDICES
        // ============================================================================
        $this->line("\n\n📋 5️⃣ ANÁLISE DE ÍNDICES");
        $this->line(str_repeat("─", 80));

        try {
            $indices = DB::select("SHOW INDEXES FROM funcionarios");
            
            $this->info("Índices encontrados:");
            foreach ($indices as $idx) {
                $type = $idx->Index_type;
                $column = $idx->Column_name;
                $cardinality = $idx->Cardinality ?? 'N/A';
                $this->info("  • {$column} ({$type}) - Cardinalidade: {$cardinality}");
            }
            
            $results['testes']['indices'] = array_map(function($idx) {
                return [
                    'nome' => $idx->Key_name,
                    'coluna' => $idx->Column_name,
                    'tipo' => $idx->Index_type,
                    'cardinalidade' => $idx->Cardinality
                ];
            }, $indices);
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Erro ao listar índices: " . $e->getMessage());
        }

        // ============================================================================
        // 6️⃣ INFORMAÇÕES DO SERVIDOR
        // ============================================================================
        $this->line("\n\n🖥️  6️⃣ INFORMAÇÕES DO SERVIDOR");
        $this->line(str_repeat("─", 80));

        try {
            $mysqlVersion = DB::select("SELECT VERSION() as version")[0]->version;
            $this->info("MySQL Version: {$mysqlVersion}");
            
            $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 'N/A';
            $this->info("Max Connections: {$maxConnections}");
            
            $innodb_buffer = DB::select("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'")[0]->Value ?? 'N/A';
            $this->info("InnoDB Buffer Pool: {$innodb_buffer}");
            
            // Tamanho da tabela
            $tableInfo = DB::select("
                SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS tamanho_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() AND table_name = 'funcionarios'
            ")[0];
            $tamanho = $tableInfo->tamanho_mb ?? 'N/A';
            $this->info("Tamanho da tabela 'funcionarios': {$tamanho} MB");
            
            $queryTime = DB::select("SHOW VARIABLES LIKE 'long_query_time'")[0]->Value ?? 'N/A';
            $this->info("Long Query Time: {$queryTime}s");
            
        } catch (\Exception $e) {
            $this->warn("⚠️  Erro ao obter informações: " . $e->getMessage());
        }

        // ============================================================================
        // 7️⃣ RESUMO E DIAGNÓSTICO
        // ============================================================================
        $this->line("\n\n" . str_repeat("═", 80));
        $this->info("📊 RESUMO E DIAGNÓSTICO");
        $this->line(str_repeat("═", 80));

        $relatorioTempo = $results['testes']['relatorio']['tempo_segundos'] ?? null;
        $buscaMatricula = $results['testes']['busca_matricula']['tempo_medio_ms'] ?? null;
        $buscaNome = $results['testes']['busca_nome']['tempo_medio_ms'] ?? null;

        $this->info("\n⚡ VELOCIDADES ESPERADAS vs REAL:");
        $this->line(str_repeat("─", 40));

        if ($relatorioTempo) {
            $esperadoRel = 25;
            $status = $relatorioTempo > $esperadoRel * 1.5 ? "❌ LENTO" : "✅ OK";
            $this->info("Relatório (92,755 regs): {$relatorioTempo}s (esperado ~{$esperadoRel}s) {$status}");
        }

        if ($buscaMatricula) {
            $esperadoMat = 8;
            $status = $buscaMatricula > $esperadoMat * 3 ? "❌ LENTO" : "✅ OK";
            $this->info("Busca Matrícula: " . number_format($buscaMatricula, 2) . "ms (esperado ~{$esperadoMat}ms) {$status}");
        }

        if ($buscaNome) {
            $esperadoNome = 150;
            $status = $buscaNome > $esperadoNome * 2 ? "❌ LENTO" : "✅ OK";
            $this->info("Busca Nome: " . number_format($buscaNome, 2) . "ms (esperado ~{$esperadoNome}ms) {$status}");
        }

        // Salvar JSON
        $logPath = storage_path('logs/benchmark_kinghost_' . date('Y-m-d_His') . '.json');
        file_put_contents($logPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("\n✅ Teste concluído - Resultado salvo em storage/logs/");
    }
}
