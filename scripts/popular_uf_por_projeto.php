<?php
// one-off: Popular UF em tabfant, locais_projeto e patr baseado em mapeamento de estados
// Uso: php scripts/populate_uf_from_project_mapping.php --execute

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$isDryRun = true;

// Verificar argumentos
if (isset($argv[1]) && $argv[1] === '--execute') {
    $isDryRun = false;
}

// Gerar log
$logDir = storage_path('logs');
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = Carbon::now()->format('Y-m-d_His');
$logFile = $logDir . "/populate_uf_{$timestamp}.log";
$handle = fopen($logFile, 'w');

function logMessage($msg) {
    global $handle;
    $time = Carbon::now()->format('Y-m-d H:i:s');
    $line = "[$time] $msg\n";
    fwrite($handle, $line);
    echo $line;
}

logMessage("═════════════════════════════════════════════════════════════════");
logMessage("🌐 SCRIPT: Preencher UF em Projetos, Locais e Patrimonios");
logMessage("═════════════════════════════════════════════════════════════════");
logMessage("Modo: " . ($isDryRun ? "DRY-RUN (sem modificação)" : "EXECUTE (modificando banco)"));
logMessage("");

try {
    // Mapeamento de UF por sigla de projeto/nome
    // Extrai UF do nome do projeto (ex: "CEF - RS" → "RS")
    $ufMapping = [
        'RS' => 'RS',
        'SC' => 'SC',
        'PR' => 'PR',
        'SP' => 'SP',
        'MG' => 'MG',
        'RJ' => 'RJ',
        'BA' => 'BA',
        'DF' => 'DF',
        'GO' => 'GO',
        'MT' => 'MT',
        'MS' => 'MS',
        'CE' => 'CE',
        'PE' => 'PE',
        'PA' => 'PA',
        'MA' => 'MA',
        'PI' => 'PI',
        'RN' => 'RN',
        'PB' => 'PB',
        'AL' => 'AL',
        'SE' => 'SE',
        'AC' => 'AC',
        'AM' => 'AM',
        'AP' => 'AP',
        'RO' => 'RO',
        'RR' => 'RR',
        'TO' => 'TO',
        'ES' => 'ES',
    ];

    // 1. Extrair UF de projetos
    logMessage("📊 Fase 1: Mapeando UF de Projetos");
    logMessage("");

    $projetos = DB::table('tabfant')->get(['id', 'CDPROJETO', 'NOMEPROJETO']);
    $projetoUFMap = [];
    $projetosComUF = 0;

    foreach ($projetos as $proj) {
        $nomeProjeto = strtoupper($proj->NOMEPROJETO);
        $uf = null;

        // Tentar extrair UF do nome (ex: "FILIAL-RS", "CEF - RS", "TJ-MG-15")
        foreach ($ufMapping as $sigla => $estadoUF) {
            if (preg_match('/(\s|-)' . $sigla . '(\s|$|-)/i', $nomeProjeto)) {
                $uf = $sigla;
                $projetoUFMap[$proj->id] = $uf;
                $projetosComUF++;
                break;
            }
        }

        if ($uf) {
            logMessage("  ✅ Projeto: $proj->NOMEPROJETO → UF=$uf");
        }
    }

    logMessage("");
    logMessage("📈 Resultado: $projetosComUF de " . count($projetos) . " projetos mapeados");
    logMessage("");

    if (!$isDryRun && count($projetoUFMap) > 0) {
        logMessage("🚀 Fase 2: Atualizando Banco de Dados");
        logMessage("");

        // Atualizar tabfant com UF
        $updateCountTabfant = 0;
        foreach ($projetoUFMap as $projectId => $uf) {
            DB::table('tabfant')->where('id', $projectId)->update(['UF' => $uf]);
            $updateCountTabfant++;
        }
        logMessage("  ✅ Atualizado TABFANT: $updateCountTabfant registros");

        // Atualizar locais_projeto com UF
        $updateCountLocais = 0;
        foreach ($projetoUFMap as $projectId => $uf) {
            $affected = DB::table('locais_projeto')
                ->where('tabfant_id', $projectId)
                ->update(['UF' => $uf]);
            $updateCountLocais += $affected;
        }
        logMessage("  ✅ Atualizado LOCAIS_PROJETO: $updateCountLocais registros");

        // Atualizar patr (patrimonios) com UF baseado em CDPROJETO
        $updateCountPatr = 0;
        $projetosCDMap = DB::table('tabfant')
            ->select('CDPROJETO', 'UF')
            ->whereIn('id', array_keys($projetoUFMap))
            ->get();

        foreach ($projetosCDMap as $proj) {
            $affected = DB::table('patr')
                ->where('CDPROJETO', $proj->CDPROJETO)
                ->update(['UF' => $proj->UF]);
            $updateCountPatr += $affected;
        }
        logMessage("  ✅ Atualizado PATR: $updateCountPatr patrimonios");

        logMessage("");
        logMessage("✅ Atualização Concluída com Sucesso");
    } else if ($isDryRun) {
        logMessage("ℹ️  DRY-RUN: Nenhuma modificação foi feita no banco");
        logMessage("Para executar, use: php scripts/populate_uf_from_project_mapping.php --execute");
    }

    logMessage("");
    logMessage("═════════════════════════════════════════════════════════════════");
    logMessage("✅ Script finalizado com sucesso");
    logMessage("═════════════════════════════════════════════════════════════════");

} catch (Exception $e) {
    logMessage("");
    logMessage("❌ ERRO: " . $e->getMessage());
    logMessage("═════════════════════════════════════════════════════════════════");
}

fclose($handle);
echo "\n📝 Log salvo em: $logFile\n";
?>
