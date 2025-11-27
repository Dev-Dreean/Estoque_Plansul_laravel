<?php
// Script PowerShell / PHP para extrair usuários do TXT

$filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

echo "=== Analisando arquivo ===\n";
echo "Arquivo: {$filePath}\n";

// Ler linhas
$lines = file($filePath, FILE_SKIP_EMPTY_LINES);

// Encontrar a linha de cabeçalho para identificar posição da coluna USUARIO
$headerLine = null;
$usuarioColumnPos = null;

foreach ($lines as $idx => $line) {
    if (strpos($line, 'USUARIO') !== false) {
        $headerLine = $idx;
        // Encontrar posição aproximada
        $usuarioColumnPos = strpos($line, 'USUARIO');
        break;
    }
}

if ($headerLine === null) {
    echo "Coluna USUARIO não encontrada\n";
    exit(1);
}

echo "Coluna USUARIO encontrada na linha: " . ($headerLine + 1) . "\n";
echo "Posição aproximada: {$usuarioColumnPos}\n\n";

// Extrair valores da coluna USUARIO
$usuarios = [];
$startData = $headerLine + 3; // Pular cabeçalho e linha de separadores

for ($i = $startData; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    // Extrair substring começando na posição
    $startPos = $usuarioColumnPos;
    $substr = substr($line, $startPos, 20);
    
    // Limpar espaços e pegar o primeiro token
    $tokens = explode(' ', trim($substr));
    $usuario = $tokens[0];
    
    // Filtrar
    if (!empty($usuario) && 
        $usuario !== '<null>' && 
        $usuario !== 'USUARIO' &&
        preg_match('/^[A-Za-z0-9._\-]+$/', $usuario) &&
        strlen($usuario) > 2 &&
        strlen($usuario) < 50
    ) {
        $usuarios[$usuario] = true;
    }
}

$usuariosUnicos = array_keys($usuarios);
sort($usuariosUnicos);

echo "=== Usuários extraídos ===\n";
echo "Total: " . count($usuariosUnicos) . "\n\n";
foreach ($usuariosUnicos as $u) {
    echo "  - {$u}\n";
}

// Salvar
file_put_contents('usuarios_extraidos.txt', implode("\n", $usuariosUnicos));
echo "\nArquivo salvo: usuarios_extraidos.txt\n";
