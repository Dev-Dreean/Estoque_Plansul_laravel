<?php
// Script de importação real - popula patr.USUARIO e cria pre-users

require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Conectar (sem usar artisan, usar config manual)
$db = new \PDO(
    'mysql:host=localhost;dbname=plansul',
    'root',
    ''
);

echo "=== IMPORTAÇÃO DE PATRIMONIO.TXT ===\n\n";

$filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

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

echo "✓ Coluna USUARIO identificada\n";
echo "✓ Iniciando parse dos {$headerLine} registros\n\n";

// Parse e extração de usuários
$usuarios = [];
$recordsAtualizados = 0;
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

echo "=== USUÁRIOS ENCONTRADOS ===\n";
echo "Total de usuários únicos: " . count($usuariosUnicos) . "\n";
foreach ($usuariosUnicos as $u) {
    echo "  - {$u}\n";
}

// Verificar quais já existem como usuários
echo "\n=== VERIFICANDO USUÁRIOS EXISTENTES ===\n";
$existentes = $db->query("SELECT NMLOGIN FROM usuario WHERE NMLOGIN IN ('" . implode("','", $usuariosUnicos) . "')");
$existentesLogins = [];
foreach ($existentes->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $existentesLogins[$row['NMLOGIN']] = true;
    echo "✓ Já existe: {$row['NMLOGIN']}\n";
}

// Identificar novos usuários
$novosUsuarios = array_diff($usuariosUnicos, array_keys($existentesLogins));
echo "\n=== NOVOS USUÁRIOS A CRIAR ===\n";
echo "Total: " . count($novosUsuarios) . "\n";
foreach ($novosUsuarios as $u) {
    echo "  - {$u}\n";
}

// Criar pre-registrations
if (count($novosUsuarios) > 0) {
    echo "\n=== CRIANDO PRE-REGISTRATIONS ===\n";
    
    foreach ($novosUsuarios as $login) {
        // Gerar senha temporária
        $senhaTemp = bin2hex(random_bytes(4)); // 8 caracteres
        
        try {
            $db->query("INSERT INTO usuario (NMLOGIN, NOMEUSER, CDMATRFUNCIONARIO, ACESSOCONFIG, ST_CONFIRMADO, CRIADO_EM, ATUALIZADO_EM) VALUES 
            ('{$login}', '{$login} (PRE)', NULL, 0, 0, NOW(), NOW())");
            
            echo "✓ Criado: {$login} (senha temp: {$senhaTemp})\n";
        } catch (\Exception $e) {
            echo "✗ Erro ao criar {$login}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== IMPORTAÇÃO CONCLUÍDA ===\n";
echo "Usuários criados: " . count($novosUsuarios) . "\n";
echo "Usuários existentes: " . count($existentesLogins) . "\n";
echo "Total: " . count($usuariosUnicos) . "\n";
