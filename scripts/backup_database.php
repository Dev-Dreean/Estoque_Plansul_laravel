<?php
/**
 * Script de backup do banco de dados antes da importação
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Patrimonio;

echo "=== BACKUP DO BANCO DE DADOS ===\n";
echo "Data: " . now()->format('d/m/Y H:i:s') . "\n\n";

// Diretório de backup
$backupDir = storage_path('backups');
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "✓ Diretório de backup criado: $backupDir\n";
}

// Nome do arquivo de backup
$backupFile = $backupDir . '/patrimonio_backup_' . now()->format('Ymd_His') . '.json';

echo "📦 Fazendo backup da tabela PATR...\n";

try {
    // Exportar todos os patrimônios
    $patrimonios = Patrimonio::all()->toArray();
    $total = count($patrimonios);
    
    $dadosBackup = [
        'data_backup' => now()->toDateTimeString(),
        'total_registros' => $total,
        'patrimonios' => $patrimonios
    ];
    
    // Salvar em JSON
    file_put_contents($backupFile, json_encode($dadosBackup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $tamanho = filesize($backupFile);
    $tamanhoMB = round($tamanho / 1024 / 1024, 2);
    
    echo "✅ Backup concluído com sucesso!\n\n";
    echo "📊 INFORMAÇÕES DO BACKUP:\n";
    echo "  - Arquivo: $backupFile\n";
    echo "  - Registros: $total\n";
    echo "  - Tamanho: $tamanhoMB MB\n\n";
    
    echo "✓ Você pode prosseguir com a importação.\n";
    echo "✓ Para restaurar, use: php scripts/restore_backup.php $backupFile\n";
    
} catch (Exception $e) {
    echo "❌ ERRO ao criar backup:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
