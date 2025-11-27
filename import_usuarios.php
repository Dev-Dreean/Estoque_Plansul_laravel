<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Patrimonio;

$filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

if (!file_exists($filePath)) {
    echo "Arquivo não encontrado: {$filePath}\n";
    exit(1);
}

echo "=== Iniciando importação de patrimônios ===\n";
echo "Arquivo: {$filePath}\n\n";

// Ler o arquivo
$content = file_get_contents($filePath);

// Dividir por linhas
$lines = explode("\n", $content);

echo "Total de linhas: " . count($lines) . "\n";

// Pular cabeçalho (primeiras linhas com nomes de colunas)
$dataLines = [];
$inData = false;
$currentRecord = [];

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Pular linhas vazias
    if (empty($line)) {
        continue;
    }
    
    // Se encontrou linha de separadores, começar a ler dados
    if (strpos($line, '====') !== false) {
        $inData = true;
        continue;
    }
    
    // Pular linhas de cabeçalho
    if (!$inData) {
        continue;
    }
    
    // Processar dados
    $dataLines[] = $line;
}

echo "Total de linhas de dados: " . count($dataLines) . "\n\n";

// Parser para tab-separated values
$records = [];
$i = 0;
while ($i < count($dataLines)) {
    $line = $dataLines[$i];
    
    // Separar por tabs
    $parts = preg_split('/\t+/', $line, -1, PREG_SPLIT_NO_EMPTY);
    
    // Se tem poucos campos, é continuação do anterior
    if (count($parts) < 5 && !empty($records)) {
        // Pular linhas de continuação por enquanto
        $i++;
        continue;
    }
    
    if (count($parts) >= 5) {
        $records[] = $parts;
    }
    
    $i++;
}

echo "Total de registros parseados: " . count($records) . "\n\n";

// Extrair usuários únicos
$usuariosUnicos = [];
echo "=== Coletando usuários únicos ===\n";

foreach ($records as $idx => $parts) {
    // Assumindo que USUARIO está em uma posição específica
    // Vou procurar por padrões como "RYAN", "BEA.SC", "TIAGO"
    foreach ($parts as $part) {
        $part = trim($part);
        
        // Filtrar valores que parecem ser logins/usuários
        // Logins típicos: letras, pontos, underscores, números
        if (!empty($part) && 
            $part !== '<null>' && 
            !is_numeric($part) &&
            strlen($part) > 2 &&
            strlen($part) < 50 &&
            preg_match('/^[A-Za-z0-9._\-]+$/', $part) &&
            !preg_match('/^(NUPATRIMONIO|SITUACAO|MARCA|CDLOCAL|MODELO|COR|DTAQUISICAO|DEHISTORICO|CDMATRFUNCIONARIO|CDPROJETO|NUDOCFISCAL|USUARIO|DTOPERACAO|NUMOF|CODOBJETO|FLCONFERIDO|N|S)$/', $part) &&
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $part) &&
            !preg_match('/^\d+$/', $part)
        ) {
            // Pode ser usuário
            if (!isset($usuariosUnicos[$part])) {
                $usuariosUnicos[$part] = 1;
            } else {
                $usuariosUnicos[$part]++;
            }
        }
    }
}

// Ordenar por frequência
arsort($usuariosUnicos);

echo "Usuários encontrados:\n";
foreach (array_slice($usuariosUnicos, 0, 30) as $user => $count) {
    echo "  - {$user} (aparece {$count} vezes)\n";
}

// Salvar para análise
file_put_contents('usuarios_encontrados.txt', implode("\n", array_keys($usuariosUnicos)));
echo "\nLista salva em: usuarios_encontrados.txt\n";
