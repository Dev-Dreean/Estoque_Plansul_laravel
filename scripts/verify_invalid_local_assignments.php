<?php
// one-off: Verificar patrimonios atribuídos a locais que não existem no seu projeto
// Uso: php scripts/verify_invalid_local_assignments.php [--export-csv] [--by-project=CDPROJETO]

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$exportCsv = false;
$filterByProject = null;

// Processar argumentos
foreach ($argv as $arg) {
    if ($arg === '--export-csv') {
        $exportCsv = true;
    }
    if (strpos($arg, '--by-project=') === 0) {
        $filterByProject = str_replace('--by-project=', '', $arg);
    }
}

// Gerar log e CSV
$logDir = storage_path('logs');
$outputDir = storage_path('output');
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$timestamp = Carbon::now()->format('Y-m-d_His');
$logFile = $logDir . "/verify_locals_{$timestamp}.log";
$csvFile = $outputDir . "/invalid_locals_{$timestamp}.csv";
$handle = fopen($logFile, 'w');

function logMessage($msg) {
    global $handle;
    $time = Carbon::now()->format('Y-m-d H:i:s');
    $line = "[$time] $msg\n";
    fwrite($handle, $line);
    echo $line;
}

logMessage("═════════════════════════════════════════════════════════════════");
logMessage("🔍 SCRIPT: Verificação de Locais Inválidos em Patrimonios");
logMessage("═════════════════════════════════════════════════════════════════");
if ($filterByProject) {
    logMessage("Filtro: Apenas projeto CDPROJETO=$filterByProject");
}
logMessage("");

try {
    // 1. Buscar todos os patrimonios com seus projetos
    $query = DB::table('patr as p')
        ->leftJoin('locais_projeto as lp', function ($join) {
            $join->on('p.CDLOCAL', '=', 'lp.cdlocal')
                ->on('p.CDPROJETO', '=', 'lp.tabfant_id');
        })
        ->select(
            'p.NUSEQPATR',
            'p.DEPATRIMONIO',
            'p.CDPROJETO',
            'p.CDLOCAL',
            'p.SITUACAO',
            'p.DTOPERACAO',
            'lp.id as local_id',
            'lp.delocal'
        );

    if ($filterByProject) {
        $query->where('p.CDPROJETO', $filterByProject);
    }

    $patrimonios = $query->get();

    logMessage("📊 Total de patrimonios analisados: " . count($patrimonios));
    logMessage("");

    // 2. Identificar patrimonios com locais inválidos
    $invalidos = [];
    $validos = [];
    $projectCounts = [];

    foreach ($patrimonios as $p) {
        if (!isset($projectCounts[$p->CDPROJETO])) {
            $projectCounts[$p->CDPROJETO] = ['total' => 0, 'invalidos' => 0];
        }
        $projectCounts[$p->CDPROJETO]['total']++;

        if (is_null($p->local_id)) {
            // Local não existe para este projeto
            $invalidos[] = $p;
            $projectCounts[$p->CDPROJETO]['invalidos']++;
        } else {
            $validos[] = $p;
        }
    }

    logMessage("⚠️  PATRIMONIOS COM LOCAIS INVÁLIDOS: " . count($invalidos));
    logMessage("");

    if (count($invalidos) > 0) {
        logMessage("📋 DETALHES DOS PATRIMONIOS INVÁLIDOS:");
        logMessage("");

        // Agrupar por projeto
        $invalidosPorProjeto = [];
        foreach ($invalidos as $p) {
            if (!isset($invalidosPorProjeto[$p->CDPROJETO])) {
                $invalidosPorProjeto[$p->CDPROJETO] = [];
            }
            $invalidosPorProjeto[$p->CDPROJETO][] = $p;
        }

        foreach ($invalidosPorProjeto as $cdProjeto => $items) {
            // Buscar nome do projeto
            $projeto = DB::table('tabfant')->where('CDPROJETO', $cdProjeto)->first();
            $nomeProjeto = $projeto ? $projeto->NOMEPROJETO : 'DESCONHECIDO';

            logMessage("  📌 PROJETO: $cdProjeto - $nomeProjeto (" . count($items) . " invalid)");
            
            // Agrupar por local
            $porLocal = [];
            foreach ($items as $p) {
                if (!isset($porLocal[$p->CDLOCAL])) {
                    $porLocal[$p->CDLOCAL] = [];
                }
                $porLocal[$p->CDLOCAL][] = $p;
            }

            foreach ($porLocal as $cdLocal => $locais) {
                logMessage("     └─ Local $cdLocal: " . count($locais) . " patrimonio(s)");
                foreach ($locais as $p) {
                    logMessage(sprintf(
                        "        • NUSEQ=%s | %s | Situacao=%s",
                        $p->NUSEQPATR,
                        $p->DEPATRIMONIO,
                        $p->SITUACAO
                    ));
                }
            }
            logMessage("");
        }
    } else {
        logMessage("✅ Nenhum patrimonio com local inválido encontrado!");
    }

    // 3. Resumo por projeto
    logMessage("📊 RESUMO POR PROJETO:");
    logMessage("");
    foreach ($projectCounts as $cdProjeto => $counts) {
        $projeto = DB::table('tabfant')->where('CDPROJETO', $cdProjeto)->first();
        $nomeProjeto = $projeto ? $projeto->NOMEPROJETO : 'DESCONHECIDO';
        $percentual = $counts['total'] > 0 ? round(($counts['invalidos'] / $counts['total']) * 100, 2) : 0;
        
        logMessage(sprintf(
            "  • %s (%s): %d total | %d invalidos (%.2f%%)",
            $cdProjeto,
            $nomeProjeto,
            $counts['total'],
            $counts['invalidos'],
            $percentual
        ));
    }
    logMessage("");

    // 4. Exportar para CSV se solicitado
    if ($exportCsv && count($invalidos) > 0) {
        $fp = fopen($csvFile, 'w');
        
        // Header
        fputcsv($fp, [
            'NUSEQPATR',
            'DEPATRIMONIO',
            'CDPROJETO',
            'NOMEPROJETO',
            'CDLOCAL',
            'SITUACAO',
            'DTOPERACAO'
        ]);

        // Dados
        foreach ($invalidos as $p) {
            $projeto = DB::table('tabfant')->where('CDPROJETO', $p->CDPROJETO)->first();
            fputcsv($fp, [
                $p->NUSEQPATR,
                $p->DEPATRIMONIO,
                $p->CDPROJETO,
                $projeto ? $projeto->NOMEPROJETO : 'DESCONHECIDO',
                $p->CDLOCAL,
                $p->SITUACAO,
                $p->DTOPERACAO
            ]);
        }

        fclose($fp);
        logMessage("📄 CSV exportado: $csvFile");
    }

    logMessage("");
    logMessage("═════════════════════════════════════════════════════════════════");
    logMessage("✅ Verificação concluída");
    logMessage("═════════════════════════════════════════════════════════════════");

} catch (Exception $e) {
    logMessage("");
    logMessage("❌ ERRO: " . $e->getMessage());
    logMessage("═════════════════════════════════════════════════════════════════");
}

fclose($handle);
echo "\n📝 Log salvo em: $logFile\n";
if ($exportCsv && count($invalidos) > 0) {
    echo "📄 CSV salvo em: $csvFile\n";
}
?>
