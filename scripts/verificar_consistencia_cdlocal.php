<?php
/**
 * Script para CORRIGIR CDLOCAL dos patrimônios
 * 
 * PROBLEMA:
 * Durante a importação, alguns patrimônios foram associados usando o ID da tabela locais_projeto
 * ao invés do campo 'cdlocal'. Isso causou inconsistências.
 * 
 * SOLUÇÃO:
 * Este script NÃO altera os dados, apenas verifica a consistência.
 * Para patrimônios onde CDLOCAL = ID do local e cdlocal = ID do local, está correto.
 * Para os demais casos, precisamos de um mapeamento manual ou do arquivo fonte correto.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICANDO CONSISTÊNCIA CDLOCAL\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Criar mapa de locais
$locaisMap = [];
$locais = LocalProjeto::all();
foreach ($locais as $local) {
    $locaisMap[$local->id] = [
        'cdlocal' => $local->cdlocal,
        'delocal' => $local->delocal,
        'tabfant_id' => $local->tabfant_id
    ];
}

// Verificar patrimônios
echo "Verificando padrões:\n\n";

// Caso 1: CDLOCAL = ID e esse local tem cdlocal = ID (CORRETO)
$corretos = 0;
// Caso 2: CDLOCAL está usando ID mas deveria usar cdlocal (INCORRETO)
$incorretos = [];
// Caso 3: CDLOCAL não existe na tabela locais_projeto (ERRO)
$naoEncontrados = 0;

$totalVerificados = 0;
$patrimonios = Patrimonio::whereNotNull('CDLOCAL')->get();

foreach ($patrimonios as $p) {
    $totalVerificados++;
    
    if (!isset($locaisMap[$p->CDLOCAL])) {
        $naoEncontrados++;
        continue;
    }
    
    $local = $locaisMap[$p->CDLOCAL];
    
    // Se CDLOCAL (ID) = cdlocal do registro, está consistente
    if ($p->CDLOCAL == $local['cdlocal']) {
        $corretos++;
    } else {
        // Possível inconsistência
        $incorretos[] = [
            'NUPATRIMONIO' => $p->NUPATRIMONIO,
            'CDLOCAL_banco' => $p->CDLOCAL,
            'local_id' => $p->CDLOCAL,
            'local_cdlocal' => $local['cdlocal'],
            'local_nome' => $local['delocal']
        ];
    }
    
    if ($totalVerificados % 1000 == 0) {
        echo "Verificados: {$totalVerificados}...\n";
    }
}

echo "\n" . str_repeat("═", 80) . "\n\n";
echo "📊 RESULTADO DA ANÁLISE:\n";
echo "Total verificados: {$totalVerificados}\n";
echo "✅ Consistentes (CDLOCAL = cdlocal): {$corretos}\n";
echo "⚠️  Possíveis inconsistências: " . count($incorretos) . "\n";
echo "❌ Locais não encontrados: {$naoEncontrados}\n\n";

if (count($incorretos) > 0) {
    echo str_repeat("═", 80) . "\n\n";
    echo "⚠️  PATRIMÔNIOS COM POSSÍVEL INCONSISTÊNCIA:\n";
    echo "(Primeiros 30 casos)\n\n";
    echo str_pad("NUPATR", 10) . str_pad("CDLOCAL", 12) . str_pad("Local ID", 12) . str_pad("Local cdlocal", 16) . "Local Nome\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach (array_slice($incorretos, 0, 30) as $inc) {
        echo str_pad($inc['NUPATRIMONIO'], 10) .
             str_pad($inc['CDLOCAL_banco'], 12) .
             str_pad($inc['local_id'], 12) .
             str_pad($inc['local_cdlocal'], 16) .
             substr($inc['local_nome'], 0, 30) . "\n";
    }
    
    if (count($incorretos) > 30) {
        echo "\n... e mais " . (count($incorretos) - 30) . " casos\n";
    }
}

echo "\n" . str_repeat("═", 80) . "\n\n";
echo "💡 PRÓXIMOS PASSOS:\n\n";
echo "O problema é que na importação:\n";
echo "• A tabela locais_projeto tem: id (PK auto increment), cdlocal (código do local)\n";
echo "• A tabela patr tem: CDLOCAL (deveria referenciar cdlocal, não id)\n\n";
echo "Exemplo patrimônio 17546:\n";
echo "• No arquivo TXT vem CDLOCAL=1\n";
echo "• Sistema importou e gravou CDLOCAL=1 na tabela patr\n";
echo "• Esse 1 está sendo interpretado como ID da tabela locais_projeto\n";
echo "• ID 1 = 'SEDE CIDASC' (cdlocal=1, tabfant_id=686)\n";
echo "• Mas o correto seria ID 8 = 'ARARANGUA' (cdlocal=8, tabfant_id=492)\n\n";

echo "PORÉM: Na maioria dos casos, cdlocal = id, então está correto!\n";
echo "Apenas " . count($incorretos) . " patrimônios têm essa diferença.\n\n";

echo "📝 RECOMENDAÇÕES:\n";
echo "1. Verificar o arquivo fonte original se os CDLOCALs estão corretos\n";
echo "2. Se o arquivo está errado, corrigir o arquivo e re-importar\n";
echo "3. Se o arquivo está certo, criar script de correção com mapeamento manual\n";
