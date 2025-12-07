<?php
// one-off: diagnóstico do filtro multi-select para supervisores

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$request = \Illuminate\Http\Request::create('/');
$response = $kernel->handle($request);

// Inicializar aplicação
$app->boot();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Log::info('🔍 [DIAGNÓSTICO] Iniciando verificação do sistema de supervisão');

// 1. Verificar se existem supervisores configurados
echo "=== VERIFICAÇÃO DE SUPERVISORES ===\n";
$supervisores = User::whereNotNull('supervisor_de')
    ->where('supervisor_de', '!=', '[]')
    ->get(['NMLOGIN', 'NOMEUSER', 'supervisor_de']);

echo "Supervisores encontrados: " . $supervisores->count() . "\n";
foreach ($supervisores as $sup) {
    $supervisionados = is_array($sup->supervisor_de) ? $sup->supervisor_de : (json_decode($sup->supervisor_de, true) ?? []);
    echo "  👤 {$sup->NMLOGIN} ({$sup->NOMEUSER}) supervisiona: " . json_encode($supervisionados) . "\n";
}

// 2. Verificar estrutura da tabela usuario
echo "\n=== ESTRUTURA DA TABELA USUARIO ===\n";
$columns = DB::select("DESCRIBE usuario");
foreach ($columns as $col) {
    if ($col->Field === 'supervisor_de') {
        echo "✅ Coluna 'supervisor_de' existe: {$col->Type}\n";
    }
}

// 3. Verificar dados na coluna supervisor_de
echo "\n=== DADOS BRUTOS NA TABELA ===\n";
$allUsers = DB::table('usuario')
    ->select('NMLOGIN', 'NOMEUSER', 'supervisor_de')
    ->whereNotNull('supervisor_de')
    ->get();

foreach ($allUsers as $u) {
    if (!empty($u->supervisor_de)) {
        echo "  {$u->NMLOGIN}: {$u->supervisor_de}\n";
    }
}

Log::info('✅ [DIAGNÓSTICO] Concluído');
echo "\n✅ Diagnóstico concluído. Verifique storage/logs/laravel.log\n";
