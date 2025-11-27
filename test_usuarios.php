<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

// Teste 1: Buscar logins distintos em patr.USUARIO
echo "=== Teste 1: Logins distintos em patr.USUARIO ===\n";
$loginsComPatrimonios = DB::table('patr')
    ->distinct()
    ->where('USUARIO', '!=', null)
    ->where('USUARIO', '!=', '')
    ->pluck('USUARIO')
    ->toArray();

echo "Logins encontrados: " . count($loginsComPatrimonios) . "\n";
print_r($loginsComPatrimonios);

// Teste 2: Verificar logins cadastrados na tabela usuario
echo "\n=== Teste 2: Logins na tabela usuario ===\n";
$loginsEmUsuario = User::pluck('NMLOGIN')->toArray();
echo "Logins em usuario: " . count($loginsEmUsuario) . "\n";
print_r($loginsEmUsuario);

// Teste 3: Processar como o controller faz
echo "\n=== Teste 3: Processamento final (como no controller) ===\n";
$cadastradores = collect($loginsComPatrimonios)->map(function ($login) {
    $user = User::where('NMLOGIN', $login)->first(['CDMATRFUNCIONARIO', 'NOMEUSER']);
    
    if ($user) {
        return (object) [
            'CDMATRFUNCIONARIO' => $user->CDMATRFUNCIONARIO,
            'NOMEUSER' => $user->NOMEUSER,
            'tipo' => 'usuario_registrado',
        ];
    } else {
        return (object) [
            'CDMATRFUNCIONARIO' => $login,
            'NOMEUSER' => $login,
            'tipo' => 'login_apenas_em_patr',
        ];
    }
})->sortBy('NOMEUSER')->values();

echo "Total de cadastradores: " . count($cadastradores) . "\n";
foreach ($cadastradores as $cad) {
    echo "  - {$cad->NOMEUSER} (CDMATRFUNCIONARIO: {$cad->CDMATRFUNCIONARIO}, tipo: {$cad->tipo})\n";
}
