<?php
/**
 * Script de Restauração de Backup
 * scripts/restore_backup.php
 * 
 * Restaura patrimônios de um backup JSON gerado durante importação
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use Illuminate\Support\Facades\DB;

echo "=== RESTAURAÇÃO DE BACKUP DE PATRIMÔNIOS ===\n";
echo "Data: " . now()->format('d/m/Y H:i:s') . "\n\n";

// Procurar backup mais recente ou aceitar como argumento
$backupFile = null;

// Verificar argumento --file
foreach ($argv as $arg) {
    if (strpos($arg, '--file=') === 0) {
        $backupFile = substr($arg, strlen('--file='));
        break;
    }
}

// Se não especificou, listar backups disponíveis
if (!$backupFile) {
    $backupDir = storage_path('backups');
    
    if (!is_dir($backupDir)) {
        die("❌ Nenhum diretório de backups encontrado em: $backupDir\n");
    }
    
    $files = glob($backupDir . '/patrimonio_backup_*.json');
    
    if (empty($files)) {
        die("❌ Nenhum backup encontrado em: $backupDir\n");
    }
    
    // Ordenar por data (mais recente primeiro)
    rsort($files);
    
    echo "📋 BACKUPS DISPONÍVEIS:\n\n";
    foreach ($files as $i => $file) {
        $filename = basename($file);
        $size = round(filesize($file) / 1024 / 1024, 2);
        $time = date('d/m/Y H:i:s', filemtime($file));
        
        echo sprintf("[%d] %s (%s MB) - %s\n", $i + 1, $filename, $size, $time);
    }
    
    echo "\n💡 Uso: php scripts/restore_backup.php --file=\"patrimonio_backup_2025_11_28_120530.json\"\n";
    echo "   ou: php scripts/restore_backup.php --file=\"armazenamento/backups/patrimonio_backup_2025_11_28_120530.json\"\n\n";
    
    die("❌ Especifique um backup com --file=\n");
}

// Validar arquivo
if (!is_file($backupFile)) {
    // Tentar em storage/backups
    $tentativa = storage_path('backups/' . basename($backupFile));
    if (is_file($tentativa)) {
        $backupFile = $tentativa;
    } else {
        die("❌ Arquivo de backup não encontrado: $backupFile\n");
    }
}

echo "📂 Arquivo: " . basename($backupFile) . "\n";
echo "💾 Tamanho: " . round(filesize($backupFile) / 1024 / 1024, 2) . " MB\n\n";

// Carregar dados
echo "📖 Lendo backup...\n";
$json = file_get_contents($backupFile);
$dados = json_decode($json, true);

if (!$dados) {
    die("❌ Erro ao decodificar JSON: " . json_last_error_msg() . "\n");
}

$totalRegistros = count($dados);
echo "✅ $totalRegistros registros encontrados no backup\n\n";

// Confirmação
echo "⚠️  AVISO: Esta operação irá:\n";
echo "   1. DELETAR todos os patrimônios atuais\n";
echo "   2. RESTAURAR os patrimonios do backup\n";
echo "   3. NÃO PODE SER DESFEITO (use backup secundário se precisar)\n\n";

echo "Digite 'CONFIRMAR' para continuar ou qualquer outra coisa para cancelar:\n";
echo "> ";

$input = trim(fgets(STDIN));

if ($input !== 'CONFIRMAR') {
    echo "\n❌ Operação cancelada.\n";
    exit(1);
}

echo "\n🔄 Iniciando restauração...\n\n";

try {
    DB::beginTransaction();
    
    // Deletar patrimônios atuais
    echo "🗑️  Deletando patrimônios atuais...";
    $deleteCount = DB::table('PATR')->delete();
    echo " ✅ $deleteCount deletados\n";
    
    // Inserir patrimonios do backup em lotes
    echo "📝 Restaurando patrimônios em lotes de 100...\n";
    
    $chunks = array_chunk($dados, 100);
    $processados = 0;
    $erros = 0;
    
    foreach ($chunks as $chunk) {
        try {
            DB::table('PATR')->insert($chunk);
            $processados += count($chunk);
            echo "   ✅ $processados/$totalRegistros restaurados\n";
        } catch (\Exception $e) {
            $erros += count($chunk);
            echo "   ⚠️  Erro ao restaurar lote: " . $e->getMessage() . "\n";
        }
    }
    
    DB::commit();
    
    echo "\n✅ RESTAURAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "   Total restaurado: $processados patrimônios\n";
    echo "   Erros: $erros\n";
    
    // Verificação final
    $totalAgora = DB::table('PATR')->count();
    echo "\n📊 Estado do banco:\n";
    echo "   Patrimônios agora: $totalAgora\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n❌ ERRO NA RESTAURAÇÃO!\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   A transação foi revertida (rollback).\n";
    
    exit(1);
}

?>
