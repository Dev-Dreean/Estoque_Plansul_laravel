#!/usr/bin/env php
<?php
/**
 * Diagnóstico de Acesso às Telas para Usuário SUP
 * Execute no servidor: php diagnostico_telas.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "\n";
echo "===============================================\n";
echo "🔍 DIAGNÓSTICO DE ACESSO ÀS TELAS - USUÁRIO SUP\n";
echo "===============================================\n\n";

// 1. Buscar usuário SUP
echo "1️⃣  Buscando usuário SUP (AOliveira)...\n";
$usuarioSup = User::where('NMLOGIN', 'AOliveira')->first();

if (!$usuarioSup) {
    echo "   ❌ Usuário AOliveira não encontrado!\n\n";
    exit(1);
}

echo "   ✅ Usuário encontrado: {$usuarioSup->NOMEUSER}\n";
echo "   PERFIL: {$usuarioSup->PERFIL}\n";
echo "   isSuperAdmin(): " . ($usuarioSup->isSuperAdmin() ? 'SIM' : 'NÃO') . "\n";
echo "   isGod(): " . ($usuarioSup->isGod() ? 'SIM' : 'NÃO') . "\n\n";

// 2. Telas cadastradas no BD
echo "2️⃣  Telas cadastradas em acessotela:\n";
$telas = DB::table('acessotela')->select('NUSEQTELA', 'DETELA', 'NIVEL_VISIBILIDADE', 'FLACESSO')->get();

if ($telas->isEmpty()) {
    echo "   ❌ NENHUMA TELA CADASTRADA NO BD!\n\n";
} else {
    echo "   Encontradas " . $telas->count() . " telas:\n\n";
    echo "   | CÓDIGO | NOME | VISIBILIDADE | ATIVO |\n";
    echo "   |--------|------|--------------|-------|\n";
    foreach ($telas as $tela) {
        printf("   | %6s | %-20s | %-12s | %s |\n", 
            $tela->NUSEQTELA, 
            substr($tela->DETELA, 0, 20), 
            $tela->NIVEL_VISIBILIDADE ?? 'TODOS',
            $tela->FLACESSO ?? 'N'
        );
    }
    echo "\n";
}

// 3. Testar temAcessoTela para cada tela
echo "3️⃣  Testando temAcessoTela() para usuário SUP:\n\n";
foreach ([1000, 1001, 1002, 1003, 1005, 1006, 1007, 1008, 1009] as $telaId) {
    $temAcesso = $usuarioSup->temAcessoTela($telaId);
    $telaVisivel = $usuarioSup->telaVisivel($telaId);
    $telaNoBd = DB::table('acessotela')->where('NUSEQTELA', $telaId)->exists();
    
    $status = $temAcesso ? '✅' : '❌';
    $visivel = $telaVisivel ? '✅' : '❌';
    $noBd = $telaNoBd ? '✅' : '❌';
    
    echo "   Tela $telaId: $status (telaVisivel: $visivel, no BD: $noBd)\n";
}

echo "\n";

// 4. Verificar se a função telaVisivel está funcional
echo "4️⃣  Testando função telaVisivel():\n";
echo "   Tela 1000 (Patrimônio):\n";
$tela1000 = DB::table('acessotela')->where('NUSEQTELA', 1000)->first();
if ($tela1000) {
    echo "      Existe no BD: ✅\n";
    echo "      NIVEL_VISIBILIDADE: " . ($tela1000->NIVEL_VISIBILIDADE ?? 'TODOS') . "\n";
    echo "      telaVisivel(1000): " . ($usuarioSup->telaVisivel(1000) ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "      ❌ NÃO EXISTE NO BD!\n";
}

echo "\n";

// 5. Verificar acessos do usuário
echo "5️⃣  Acessos do usuário em acessousuario:\n";
$acessos = DB::table('acessousuario')
    ->where('CDMATRFUNCIONARIO', $usuarioSup->CDMATRFUNCIONARIO)
    ->get();

if ($acessos->isEmpty()) {
    echo "   ⚠️  Usuário SUP não tem registros em acessousuario\n";
    echo "      (Isto é NORMAL para Super Admin - ele tem acesso automático)\n";
} else {
    echo "   Encontrados " . $acessos->count() . " registros:\n";
    foreach ($acessos as $acesso) {
        echo "      Tela {$acesso->NUSEQTELA}: {$acesso->INACESSO}\n";
    }
}

echo "\n";

// 6. Conclusão
echo "6️⃣  ANÁLISE FINAL:\n";
if (!$usuarioSup->isSuperAdmin()) {
    echo "   ❌ ERRO: Usuário não é Super Admin!\n";
} elseif ($telas->isEmpty()) {
    echo "   ❌ ERRO: Nenhuma tela cadastrada em acessotela!\n";
    echo "      SOLUÇÃO: Execute 'php artisan cadastro-tela:sync'\n";
} else {
    $todasDisponiveis = true;
    foreach ([1000, 1001, 1002, 1003, 1005, 1006, 1007, 1008, 1009] as $telaId) {
        if (!$usuarioSup->temAcessoTela($telaId)) {
            $todasDisponiveis = false;
            break;
        }
    }
    
    if ($todasDisponiveis) {
        echo "   ✅ FUNCIONANDO: Super Admin tem acesso a todas as telas!\n";
    } else {
        echo "   ⚠️  AVISO: Algumas telas não estão acessíveis\n";
        echo "      POSSÍVEL CAUSA: Telas com NIVEL_VISIBILIDADE = 'SUP' ou 'ADM'\n";
    }
}

echo "\n===============================================\n\n";
