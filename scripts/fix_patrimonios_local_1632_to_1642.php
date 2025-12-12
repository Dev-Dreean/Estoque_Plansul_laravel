<?php
// one-off: Corrigir patrimonios no projeto 999915 que estão com local 1632 para local 1642
// Uso: php scripts/fix_patrimonios_local_1632_to_1642.php --execute

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
$logFile = $logDir . "/fix_patrimonios_{$timestamp}.log";
$handle = fopen($logFile, 'w');

function logMessage($msg) {
    global $handle;
    $time = Carbon::now()->format('Y-m-d H:i:s');
    $line = "[$time] $msg\n";
    fwrite($handle, $line);
    echo $line;
}

logMessage("═════════════════════════════════════════════════════════════════");
logMessage("🔍 SCRIPT: Corrigir Patrimonios Local 1632 → 1642 | Projeto 999915");
logMessage("═════════════════════════════════════════════════════════════════");
logMessage("Modo: " . ($isDryRun ? "DRY-RUN (sem modificação)" : "EXECUTE (modificando banco)"));
logMessage("");

try {
    // 1. Validar que o projeto existe
    $projeto = DB::table('tabfant')->where('CDPROJETO', '999915')->first();
    if (!$projeto) {
        throw new Exception("❌ Projeto 999915 não encontrado!");
    }
    logMessage("✅ Projeto encontrado: ID={$projeto->id}, Nome={$projeto->NOMEPROJETO}");
    logMessage("");

    // 2. Validar que os locais existem
    $local1632 = DB::table('locais_projeto')->where('cdlocal', '1632')->where('tabfant_id', $projeto->id)->first();
    $local1642 = DB::table('locais_projeto')->where('cdlocal', '1642')->where('tabfant_id', $projeto->id)->first();

    if (!$local1632) {
        logMessage("⚠️  Local 1632 não encontrado no projeto 999915");
    } else {
        logMessage("✅ Local 1632 encontrado: ID={$local1632->id}, Nome={$local1632->delocal}");
    }

    if (!$local1642) {
        throw new Exception("❌ Local 1642 não encontrado no projeto 999915!");
    }
    logMessage("✅ Local 1642 encontrado: ID={$local1642->id}, Nome={$local1642->delocal}");
    logMessage("");

    // 3. Buscar patrimônios com local 1632 no projeto 999915
    $patrimoniosErrados = DB::table('patr')
        ->where('CDPROJETO', '999915')
        ->where('CDLOCAL', '1632')
        ->get();

    logMessage("📊 Patrimonios encontrados com local 1632: " . count($patrimoniosErrados));
    logMessage("");

    if (count($patrimoniosErrados) == 0) {
        logMessage("✅ Nenhum patrimonio encontrado com local 1632. Nada a fazer!");
        fclose($handle);
        exit(0);
    }

    // 4. Listar patrimônios que serão corrigidos
    logMessage("📋 PATRIMÔNIOS A CORRIGIR:");
    logMessage("");
    
    $nuseqList = [];
    foreach ($patrimoniosErrados as $patr) {
        $nuseqList[] = $patr->NUSEQPATR;
        logMessage(sprintf(
            "  • NUSEQ=%s | Patrimonio=%s | Situacao=%s | Func=%s",
            $patr->NUSEQPATR,
            $patr->DEPATRIMONIO,
            $patr->SITUACAO,
            $patr->CDMATRFUNCIONARIO ?? 'N/A'
        ));
    }
    logMessage("");

    if (!$isDryRun) {
        logMessage("🚀 Iniciando atualização no banco de dados...");
        logMessage("");

        // 5. Atualizar patrimonios
        $updated = DB::table('patr')
            ->where('CDPROJETO', '999915')
            ->where('CDLOCAL', '1632')
            ->update([
                'CDLOCAL' => '1642',
                'DTOPERACAO' => now(),
                'USUARIO' => 'AUTO'
            ]);

        logMessage("✅ Atualização concluída: $updated registros modificados");
        logMessage("");

        // 6. Verificação pós-atualização
        $verificacao = DB::table('patr')
            ->where('CDPROJETO', '999915')
            ->where('CDLOCAL', '1642')
            ->whereIn('NUSEQPATR', $nuseqList)
            ->count();

        logMessage("📊 Verificação pós-atualização:");
        logMessage("  • Patrimonios com local 1642: $verificacao (esperado: " . count($patrimoniosErrados) . ")");
        
        if ($verificacao === count($patrimoniosErrados)) {
            logMessage("✅ Atualização validada com sucesso!");
        } else {
            logMessage("⚠️  Verificação incompleta. Esperado: " . count($patrimoniosErrados) . ", Encontrado: $verificacao");
        }
    } else {
        logMessage("ℹ️  DRY-RUN: Nenhuma modificação foi feita no banco");
        logMessage("Para executar, use: php scripts/fix_patrimonios_local_1632_to_1642.php --execute");
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
