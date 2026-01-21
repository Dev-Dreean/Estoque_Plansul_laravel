<?php
// one-off: Teste de velocidade completo do sistema no KingHost
// Mede: relatório, buscas, índices, performance do servidor

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

echo "\n╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                    🚀 BENCHMARK KINGHOST - TESTE DE VELOCIDADE                 ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'testes' => []
];

// ============================================================================
// 1️⃣ TESTE DE RELATÓRIO (Simular geração do CSV)
// ============================================================================
echo "\n📊 1️⃣ TESTE DE GERAÇÃO DO RELATÓRIO (92,755 registros)\n";
echo str_repeat("─", 80) . "\n";

$inicioRelatorio = microtime(true);
$totalRecords = 0;
$batchSize = 1000;

// try {
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
    
    echo "⏱️  Contando registros totais...\n";
    $contagem = $query->count();
    echo "   Total: {$contagem} registros\n\n";
    
    echo "⏱️  Iterando com cursor (batches de {$batchSize})...\n";
    $inicio = microtime(true);
    
    $query->cursor()
        ->each(function ($record) use (&$totalRecords) {
            $totalRecords++;
            // Simular processamento (como no streaming)
            if ($totalRecords % 10000 == 0) {
                echo "   ✓ {$totalRecords} registros processados\n";
            }
        });
    
    $tempoRelatorio = microtime(true) - $inicio;
    
    echo "\n✅ Relatório gerado em " . number_format($tempoRelatorio, 2) . "s\n";
    echo "   Velocidade: " . number_format($contagem / $tempoRelatorio, 0) . " registros/segundo\n";
    
    $results['testes']['relatorio'] = [
        'tempo_segundos' => round($tempoRelatorio, 2),
        'registros' => $contagem,
        'velocidade_por_seg' => round($contagem / $tempoRelatorio, 2)
    ];
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    $results['testes']['relatorio'] = ['erro' => $e->getMessage()];
}

// ============================================================================
// 2️⃣ TESTE DE BUSCA POR MATRÍCULA (1-8ms esperado)
// ============================================================================
echo "\n\n🔍 2️⃣ TESTE DE BUSCA POR MATRÍCULA\n";
echo str_repeat("─", 80) . "\n";

$buscasMatricula = ['188252', '20', '999989', '456789', '123456'];
$temposMatricula = [];

foreach ($buscasMatricula as $matricula) {
    $inicio = microtime(true);
    
    $resultado = DB::table('funcionarios')
        ->where('CDMATRFUNCIONARIO', '=', $matricula)
        ->first();
    
    $tempo = (microtime(true) - $inicio) * 1000; // em ms
    $temposMatricula[] = $tempo;
    
    $status = $resultado ? '✅ ENCONTRADO' : '⚠️  NÃO ENCONTRADO';
    echo "Busca por CDMATRFUNCIONARIO={$matricula}: {$tempo}ms {$status}\n";
}

$mediaMatricula = array_sum($temposMatricula) / count($temposMatricula);
echo "\n📈 Média de tempo para busca por matrícula: " . number_format($mediaMatricula, 2) . "ms\n";

$results['testes']['busca_matricula'] = [
    'tempo_medio_ms' => round($mediaMatricula, 2),
    'tempos_individuais' => array_map(fn($t) => round($t, 2), $temposMatricula)
];

// ============================================================================
// 3️⃣ TESTE DE BUSCA POR NOME (LIKE e FULLTEXT)
// ============================================================================
echo "\n\n🔍 3️⃣ TESTE DE BUSCA POR NOME\n";
echo str_repeat("─", 80) . "\n";

$busdasNome = ['ABIGA', 'JO%', 'MAR%', 'SILVA', 'SANTOS'];
$temposNome = [];

foreach ($busdasNome as $nome) {
    $inicio = microtime(true);
    
    $resultado = DB::table('funcionarios')
        ->where('NMFUNCIONARIO', 'LIKE', "{$nome}%")
        ->count();
    
    $tempo = (microtime(true) - $inicio) * 1000;
    $temposNome[] = $tempo;
    
    echo "Busca LIKE '{$nome}%': {$tempo}ms ({$resultado} resultados)\n";
}

$mediaNome = array_sum($temposNome) / count($temposNome);
echo "\n📈 Média de tempo para busca por nome (LIKE): " . number_format($mediaNome, 2) . "ms\n";

$results['testes']['busca_nome'] = [
    'tempo_medio_ms' => round($mediaNome, 2),
    'tempos_individuais' => array_map(fn($t) => round($t, 2), $temposNome)
];

// ============================================================================
// 4️⃣ TESTE DE BUSCA FULLTEXT
// ============================================================================
echo "\n\n🔍 4️⃣ TESTE DE BUSCA FULLTEXT\n";
echo str_repeat("─", 80) . "\n";

$buscasFulltext = ['ABIGAIL', 'JOAO', 'MARIA'];
$temposFulltext = [];

foreach ($buscasFulltext as $termo) {
    $inicio = microtime(true);
    
    $resultado = DB::table('funcionarios')
        ->whereRaw("MATCH(NMFUNCIONARIO) AGAINST(? IN BOOLEAN MODE)", ["+{$termo}*"])
        ->count();
    
    $tempo = (microtime(true) - $inicio) * 1000;
    $temposFulltext[] = $tempo;
    
    echo "FULLTEXT MATCH '{$termo}*': {$tempo}ms ({$resultado} resultados)\n";
}

$mediaFulltext = array_sum($temposFulltext) / count($temposFulltext);
echo "\n📈 Média de tempo para FULLTEXT MATCH: " . number_format($mediaFulltext, 2) . "ms\n";

$results['testes']['busca_fulltext'] = [
    'tempo_medio_ms' => round($mediaFulltext, 2),
    'tempos_individuais' => array_map(fn($t) => round($t, 2), $temposFulltext)
];

// ============================================================================
// 5️⃣ ANÁLISE DE ÍNDICES
// ============================================================================
echo "\n\n📋 5️⃣ ANÁLISE DE ÍNDICES\n";
echo str_repeat("─", 80) . "\n";

try {
    $indices = DB::select("SHOW INDEXES FROM funcionarios");
    
    echo "Índices encontrados:\n";
    foreach ($indices as $idx) {
        $type = $idx->Index_type;
        $column = $idx->Column_name;
        $cardinality = $idx->Cardinality ?? 'N/A';
        echo "  • {$column} ({$type}) - Cardinalidade: {$cardinality}\n";
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
    echo "⚠️  Erro ao listar índices: " . $e->getMessage() . "\n";
}

// ============================================================================
// 6️⃣ INFORMAÇÕES DO SERVIDOR
// ============================================================================
echo "\n\n🖥️  6️⃣ INFORMAÇÕES DO SERVIDOR\n";
echo str_repeat("─", 80) . "\n";

try {
    $mysqlVersion = DB::select("SELECT VERSION() as version")[0]->version;
    echo "MySQL Version: {$mysqlVersion}\n";
    
    $maxConnections = DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 'N/A';
    echo "Max Connections: {$maxConnections}\n";
    
    $innodb_buffer = DB::select("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'")[0]->Value ?? 'N/A';
    echo "InnoDB Buffer Pool: {$innodb_buffer}\n";
    
    // Tamanho da tabela
    $tableSize = DB::select("
        SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS tamanho_mb
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() AND table_name = 'funcionarios'
    ")[0]->tamanho_mb ?? 'N/A';
    echo "Tamanho da tabela 'funcionarios': {$tableSize} MB\n";
    
    // Query time
    $queryTime = DB::select("SHOW VARIABLES LIKE 'long_query_time'")[0]->Value ?? 'N/A';
    echo "Long Query Time: {$queryTime}s\n";
    
} catch (\Exception $e) {
    echo "⚠️  Erro ao obter informações: " . $e->getMessage() . "\n";
}

// ============================================================================
// 7️⃣ RESUMO E DIAGNÓSTICO
// ============================================================================
echo "\n\n" . str_repeat("═", 80) . "\n";
echo "📊 RESUMO E DIAGNÓSTICO\n";
echo str_repeat("═", 80) . "\n";

$relatorioTempo = $results['testes']['relatorio']['tempo_segundos'] ?? null;
$buscaMatricula = $results['testes']['busca_matricula']['tempo_medio_ms'] ?? null;
$buscaNome = $results['testes']['busca_nome']['tempo_medio_ms'] ?? null;

echo "\n⚡ VELOCIDADES ESPERADAS vs REAL:\n";
echo "────────────────────────────────────────────\n";

if ($relatorioTempo) {
    $esperadoRel = 25; // segundos em KingHost
    $status = $relatorioTempo > $esperadoRel * 1.5 ? "❌ LENTO" : "✅ OK";
    echo "Relatório (92,755 regs):\n";
    echo "  Esperado: ~{$esperadoRel}s | Real: {$relatorioTempo}s {$status}\n";
}

if ($buscaMatricula) {
    $esperadoMat = 8; // ms
    $status = $buscaMatricula > $esperadoMat * 3 ? "❌ LENTO" : "✅ OK";
    echo "\nBusca por Matrícula:\n";
    echo "  Esperado: ~{$esperadoMat}ms | Real: " . number_format($buscaMatricula, 2) . "ms {$status}\n";
}

if ($buscaNome) {
    $esperadoNome = 150; // ms
    $status = $buscaNome > $esperadoNome * 2 ? "❌ LENTO" : "✅ OK";
    echo "\nBusca por Nome (LIKE):\n";
    echo "  Esperado: ~{$esperadoNome}ms | Real: " . number_format($buscaNome, 2) . "ms {$status}\n";
}

echo "\n\n🔍 POSSÍVEIS GARGALOS:\n";
echo "────────────────────────────────────────────\n";

$gargalos = [];

if ($relatorioTempo && $relatorioTempo > 40) {
    $gargalos[] = "❌ Relatório MUITO LENTO (>{$relatorioTempo}s)";
}

if ($buscaMatricula && $buscaMatricula > 25) {
    $gargalos[] = "❌ Busca por matrícula LENTA (>{$buscaMatricula}ms)";
}

if ($buscaNome && $buscaNome > 300) {
    $gargalos[] = "❌ Busca por nome LENTA (>{$buscaNome}ms)";
}

if (empty($gargalos)) {
    echo "✅ Nenhum gargalo detectado\n";
} else {
    foreach ($gargalos as $gargalo) {
        echo "{$gargalo}\n";
    }
}

echo "\n\n💡 RECOMENDAÇÕES:\n";
echo "────────────────────────────────────────────\n";
echo "1. Se relatório está lento: verificar limite de memória PHP\n";
echo "2. Se buscas lentas: confirmar índices estão ativos (SHOW INDEX)\n";
echo "3. Se tudo lento: revisar conexão SSH/network latency\n";
echo "4. Coletar output deste teste para análise detalhada\n";

echo "\n✅ Teste concluído em " . date('Y-m-d H:i:s') . "\n\n";

// Salvar resultado em arquivo
$logPath = storage_path('logs/benchmark_kinghost_' . date('Y-m-d_His') . '.json');
file_put_contents($logPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "📁 Resultado salvo em: {$logPath}\n\n";
