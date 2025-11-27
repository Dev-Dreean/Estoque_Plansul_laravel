<?php
// Script de importação real com Laravel - popula dados e cria pre-users

require 'bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "=== IMPORTAÇÃO DE PATRIMONIO.TXT ===\n\n";

$filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

if (!file_exists($filePath)) {
    echo "[ERRO] Arquivo não encontrado: {$filePath}\n";
    exit(1);
}

// Ler linhas
$lines = file($filePath, FILE_SKIP_EMPTY_LINES);

// Encontrar coluna USUARIO
$headerLine = null;
$usuarioColumnPos = null;

foreach ($lines as $idx => $line) {
    if (strpos($line, 'USUARIO') !== false) {
        $headerLine = $idx;
        $usuarioColumnPos = strpos($line, 'USUARIO');
        break;
    }
}

if ($headerLine === null) {
    echo "[ERRO] Coluna USUARIO não encontrada\n";
    exit(1);
}

echo "✓ Coluna USUARIO identificada (linha " . ($headerLine + 1) . ")\n";

// Parse e extração de usuários
$usuarios = [];
$startData = $headerLine + 3;

for ($i = $startData; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    $startPos = $usuarioColumnPos;
    $substr = substr($line, $startPos, 20);
    
    $tokens = explode(' ', trim($substr));
    $usuario = $tokens[0];
    
    if (!empty($usuario) && 
        $usuario !== '<null>' && 
        preg_match('/^[A-Za-z0-9._\-]+$/', $usuario) &&
        strlen($usuario) > 2 &&
        strlen($usuario) < 50
    ) {
        $usuarios[$usuario] = true;
    }
}

$usuariosUnicos = array_keys($usuarios);
sort($usuariosUnicos);

echo "✓ Encontrados " . count($usuariosUnicos) . " usuários únicos\n\n";

// Verificar quais já existem
$existentes = User::whereIn('NMLOGIN', $usuariosUnicos)->pluck('NMLOGIN')->toArray();
$novosUsuarios = array_diff($usuariosUnicos, $existentes);

echo "=== RESUMO ===\n";
echo "Usuários já existentes: " . count($existentes) . "\n";
foreach ($existentes as $e) {
    echo "  ✓ {$e}\n";
}

echo "\nNovos usuários a criar: " . count($novosUsuarios) . "\n";
foreach ($novosUsuarios as $n) {
    echo "  + {$n}\n";
}

// Criar pre-registrations
if (count($novosUsuarios) > 0) {
    echo "\n=== CRIANDO PRE-REGISTRATIONS ===\n";
    
    foreach ($novosUsuarios as $login) {
        try {
            $user = new User();
            $user->NMLOGIN = $login;
            $user->NOMEUSER = "{$login} (PRE)";
            $user->PERFIL = 'USR';
            $user->SENHA = Hash::make('temporaria123'); // senha temporária padrão
            $user->LGATIVO = 1;
            $user->save();
            
            echo "✓ Criado: {$login}\n";
        } catch (\Exception $e) {
            echo "✗ Erro ao criar {$login}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== IMPORTAÇÃO CONCLUÍDA ===\n";
echo "Total de usuários a criar: " . count($novosUsuarios) . "\n";
echo "Total de usuários existentes: " . count($existentes) . "\n";
echo "Total geral: " . count($usuariosUnicos) . "\n";
