<?php
/**
 * Script de Verificação de Ambiente para Servidor
 * scripts/check_server_environment.php
 * 
 * Verifica se o servidor está pronto para importação
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAÇÃO DE AMBIENTE DO SERVIDOR ===\n";
echo "Data: " . date('d/m/Y H:i:s') . "\n\n";

$tudo_ok = true;

// 1. Verificar PHP
echo "📋 [1/10] PHP\n";
echo "   Versão: " . phpversion() . "\n";
if (version_compare(phpversion(), '8.0', '>=')) {
    echo "   ✅ Versão adequada\n";
} else {
    echo "   ❌ ERRO: PHP 8.0+ requerido\n";
    $tudo_ok = false;
}

// 2. Verificar extensões PHP
echo "\n📋 [2/10] Extensões PHP\n";
$extensoes_requeridas = ['mb_string', 'json', 'pdo_mysql'];
foreach ($extensoes_requeridas as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext\n";
    } else {
        echo "   ❌ $ext - NÃO INSTALADA\n";
        $tudo_ok = false;
    }
}

// 3. Verificar conexão banco de dados
echo "\n📋 [3/10] Banco de Dados\n";
try {
    $count = DB::table('PATR')->count();
    echo "   ✅ Conectado ao MySQL\n";
    echo "   📊 Patrimônios atuais: $count\n";
} catch (\Exception $e) {
    echo "   ❌ ERRO ao conectar: " . $e->getMessage() . "\n";
    $tudo_ok = false;
}

// 4. Verificar espaço em disco
echo "\n📋 [4/10] Espaço em Disco\n";
$storage_path = storage_path();
$disco_livre = disk_free_space($storage_path);
$disco_total = disk_total_space($storage_path);

if ($disco_livre !== false && $disco_total !== false) {
    $percentual = round($disco_livre / $disco_total * 100, 2);
    $livre_mb = round($disco_livre / 1024 / 1024, 2);
    
    echo "   Total: " . round($disco_total / 1024 / 1024 / 1024, 2) . " GB\n";
    echo "   Livre: " . $livre_mb . " MB ($percentual%)\n";
    
    if ($disco_livre > 100 * 1024 * 1024) { // 100MB mínimo
        echo "   ✅ Espaço adequado\n";
    } else {
        echo "   ❌ Espaço insuficiente (< 100 MB)\n";
        $tudo_ok = false;
    }
} else {
    echo "   ⚠️  Não foi possível verificar espaço em disco\n";
}

// 5. Verificar diretório de storage
echo "\n📋 [5/10] Diretório storage/\n";
$dirs = [
    'storage/backups' => 'Backups',
    'storage/logs' => 'Logs',
    'storage/logs/imports' => 'Import Logs',
];

foreach ($dirs as $dir => $label) {
    $path = base_path($dir);
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "   ✅ $label ($dir)\n";
        } else {
            echo "   ❌ $label NÃO TEM PERMISSÃO DE ESCRITA ($dir)\n";
            $tudo_ok = false;
        }
    } else {
        // Tentar criar
        if (@mkdir($path, 0755, true)) {
            echo "   ✅ $label CRIADO ($dir)\n";
        } else {
            echo "   ❌ $label NÃO PODE SER CRIADO ($dir)\n";
            $tudo_ok = false;
        }
    }
}

// 6. Verificar arquivo patrimonio.TXT
echo "\n📋 [6/10] Arquivo patrimonio.TXT\n";
require_once __DIR__ . '/PathDetector.php';
$pathDetector = new PathDetector();
[$encontrado, $resultado] = $pathDetector->findPatrimonioFile();

if ($encontrado) {
    echo "   ✅ Encontrado em: $resultado\n";
} else {
    echo "   ⚠️  Arquivo não encontrado (será necessário antes de importar)\n";
}

// 7. Verificar Models
echo "\n📋 [7/10] Models Laravel\n";
$models = [
    'App\\Models\\Patrimonio',
    'App\\Models\\User',
    'App\\Models\\Funcionario',
    'App\\Models\\Tabfant',
    'App\\Models\\LocalProjeto',
    'App\\Models\\ObjetoPatr',
];

foreach ($models as $model) {
    if (class_exists($model)) {
        echo "   ✅ " . class_basename($model) . "\n";
    } else {
        echo "   ❌ " . class_basename($model) . " NÃO ENCONTRADO\n";
        $tudo_ok = false;
    }
}

// 8. Verificar arquivos de script
echo "\n📋 [8/10] Scripts de Importação\n";
$scripts = [
    'scripts/import_patrimonio_completo.php',
    'scripts/backup_database.php',
    'scripts/restore_backup.php',
    'scripts/config-import.php',
    'scripts/PathDetector.php',
];

foreach ($scripts as $script) {
    if (file_exists(base_path($script))) {
        echo "   ✅ " . basename($script) . "\n";
    } else {
        echo "   ❌ " . basename($script) . " NÃO ENCONTRADO\n";
        $tudo_ok = false;
    }
}

// 9. Verificar artisan
echo "\n📋 [9/10] Artisan CLI\n";
if (file_exists(base_path('artisan'))) {
    echo "   ✅ artisan encontrado\n";
} else {
    echo "   ❌ artisan NÃO ENCONTRADO\n";
    $tudo_ok = false;
}

// 10. Verificar ambiente Laravel
echo "\n📋 [10/10] Configuração Laravel\n";
echo "   Ambiente: " . config('app.env') . "\n";
echo "   Debug: " . (config('app.debug') ? 'Ativo' : 'Inativo') . "\n";
echo "   Timezone: " . config('app.timezone') . "\n";

if (config('app.env') === 'production') {
    echo "   ✅ Ambiente de produção detectado\n";
} else {
    echo "   ⚠️  Ambiente: " . config('app.env') . " (certifique-se que é correto)\n";
}

// Resumo
echo "\n" . str_repeat("=", 50) . "\n";

if ($tudo_ok) {
    echo "✅ SERVIDOR PRONTO PARA IMPORTAÇÃO!\n";
    echo "\nPróximos passos:\n";
    echo "   1. Upload do arquivo patrimonio.TXT para servidor\n";
    echo "   2. Executar: php scripts/backup_database.php\n";
    echo "   3. Executar: php scripts/import_patrimonio_completo.php\n";
    exit(0);
} else {
    echo "❌ PROBLEMAS DETECTADOS!\n";
    echo "\nResolva os erros acima antes de continuar.\n";
    echo "Entre em contato com suporte se necessário.\n";
    exit(1);
}

?>
