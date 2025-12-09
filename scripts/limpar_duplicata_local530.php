<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "═══════════════════════════════════════\n";
echo "LIMPAR DUPLICATAS: CDLOCAL=530\n";
echo "═══════════════════════════════════════\n\n";

// Ver todos os registros com cdlocal=530
$duplicatas = DB::table('locais_projeto')
    ->where('cdlocal', 530)
    ->orderBy('id')
    ->get();

echo "Registros encontrados com CDLOCAL=530:\n";
echo "─────────────────────────────────────────\n";

foreach ($duplicatas as $dup) {
    echo "  ID {$dup->id}: {$dup->delocal}\n";
    
    // Ver quantos patrimônios usam este registro
    $count = DB::table('patr')->where('CDLOCAL', 530)->count();
    echo "    Patrimônios usando CDLOCAL 530: $count\n\n";
}

echo "═══════════════════════════════════════\n";
echo "DECISÃO:\n";
echo "  ✅ Manter ID 72 (ESCRITORIO SC)\n";
echo "  ❌ Deletar ID 526 (CANAAN - duplicata errada)\n";
echo "═══════════════════════════════════════\n\n";

echo "Executar deleção? Digite 'SIM' para confirmar: ";
$confirmacao = trim(fgets(STDIN));

if (strtoupper($confirmacao) === 'SIM') {
    DB::beginTransaction();
    try {
        $deleted = DB::table('locais_projeto')
            ->where('id', 526)
            ->where('cdlocal', 530)
            ->where('delocal', 'CANAAN')
            ->delete();
        
        DB::commit();
        echo "\n✅ Deletado: $deleted registro(s)\n";
        
        // Verificar resultado
        $restantes = DB::table('locais_projeto')->where('cdlocal', 530)->count();
        echo "✓ Registros restantes com CDLOCAL=530: $restantes\n";
        
        if ($restantes === 1) {
            $unico = DB::table('locais_projeto')->where('cdlocal', 530)->first();
            echo "✓ Registro único: ID {$unico->id} - {$unico->delocal}\n";
        }
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n⚠️  Operação cancelada.\n";
}

echo "\n═══════════════════════════════════════\n";
