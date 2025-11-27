<?php
// Importador simple - apenas extrai e mostra usuários para você validar

$filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

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

$usuarios = [];
$startData = $headerLine + 3;
for ($i = $startData; $i < count($lines); $i++) {
    $line = $lines[$i];
    $startPos = $usuarioColumnPos;
    $substr = substr($line, $startPos, 20);
    $tokens = explode(' ', trim($substr));
    $usuario = $tokens[0];
    if (!empty($usuario) && $usuario !== '<null>' && preg_match('/^[A-Za-z0-9._\-]+$/', $usuario) && strlen($usuario) > 2 && strlen($usuario) < 50) {
        $usuarios[$usuario] = true;
    }
}

$usuariosUnicos = array_keys($usuarios);
sort($usuariosUnicos);

echo "=== USUÁRIOS EXTRAÍDOS DO PATRIMONIO.TXT ===\n";
echo "Total: " . count($usuariosUnicos) . "\n\n";

$sqlInserts = [];
foreach ($usuariosUnicos as $login) {
    // Gerar SQL para criar pre-users
    $nome = "{$login} (PRE)";
    $sql = "INSERT INTO usuario (NMLOGIN, NOMEUSER, PERFIL, SENHA, LGATIVO) VALUES ('$login', '$nome', 'USR', SHA2('temporaria123', 256), 1);";
    $sqlInserts[] = $sql;
    echo $login . "\n";
}

// Salvar como arquivo SQL
$sqlContent = "-- Script de importação de usuários\n";
$sqlContent .= "-- Execute este arquivo no MySQL\n\n";
$sqlContent .= implode("\n", $sqlInserts);

file_put_contents('usuarios_insert.sql', $sqlContent);

echo "\n=== ARQUIVO SQL GERADO ===\n";
echo "Arquivo: usuarios_insert.sql\n";
echo "Total de inserts: " . count($sqlInserts) . "\n";
echo "\nCopy/Paste no MySQL:\n";
echo str_repeat("-", 50) . "\n";
foreach ($sqlInserts as $sql) {
    echo $sql . "\n";
}
