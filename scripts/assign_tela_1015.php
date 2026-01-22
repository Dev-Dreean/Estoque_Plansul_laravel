<?php
// one-off: Atribuir tela 1015 (Cancelar) aos usuários apropriados

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════\n";
echo "ATRIBUIÇÃO DE PERMISSÃO TELA 1015 (CANCELAR)\n";
echo "═══════════════════════════════════════════════\n\n";

// Usuários que devem ter permissão para CANCELAR (mesmo de APROVAR)
$usuarios_cancelar = [
    '182687' => 'BEATRIZ',  // Já tem 1014 (Aprovar)
];

$tela_cancelar = 1015;

foreach ($usuarios_cancelar as $cdmatr => $nome) {
    $exists = DB::table('acessousuario')
        ->where('CDMATRFUNCIONARIO', $cdmatr)
        ->where('NUSEQTELA', $tela_cancelar)
        ->exists();
    
    if ($exists) {
        echo "ℹ️  $nome já tem tela 1015\n";
    } else {
        DB::table('acessousuario')->insert([
            'NUSEQTELA' => $tela_cancelar,
            'CDMATRFUNCIONARIO' => $cdmatr,
            'INACESSO' => 'S'
        ]);
        echo "✅ Atribuído 1015 a $nome ($cdmatr)\n";
    }
}

echo "\n═══ VERIFICAÇÃO FINAL ═══\n";
$count = DB::table('acessousuario')->where('NUSEQTELA', 1015)->count();
echo "Total de usuários com TELA 1015: $count\n";

if ($count > 0) {
    echo "\nDetalhes:\n";
    $users = DB::table('acessousuario as a')
        ->join('usuario as u', 'a.CDMATRFUNCIONARIO', '=', 'u.CDMATRFUNCIONARIO')
        ->where('a.NUSEQTELA', 1015)
        ->select('u.NOMEUSER', 'a.CDMATRFUNCIONARIO')
        ->get();
    
    foreach ($users as $user) {
        echo "  ✅ {$user->NOMEUSER} ({$user->CDMATRFUNCIONARIO})\n";
    }
}

echo "\n✅ Concluído!\n";
?>
