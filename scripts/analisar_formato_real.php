<?php

// Análise do formato real do arquivo Patrimonio.txt

$arquivo = '/home/plansul/www/estoque-laravel/patrimonio.TXT';

if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado: $arquivo\n");
}

echo "📄 Analisando estrutura real do arquivo...\n\n";

$linhas = file($arquivo, FILE_IGNORE_NEW_LINES);
echo "Total de linhas: " . count($linhas) . "\n\n";

// Analisar primeiras 20 linhas
echo "═══════════════════════════════════════════════════════════════\n";
echo "PRIMEIRAS 20 LINHAS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

for ($i = 0; $i < min(20, count($linhas)); $i++) {
    $linha = $linhas[$i];
    $tamanho = strlen($linha);
    
    echo "Linha $i (tamanho: $tamanho):\n";
    echo "├─ Conteúdo: " . substr($linha, 0, 100) . ($tamanho > 100 ? '...' : '') . "\n";
    
    // Mostrar primeiros 50 caracteres com posições
    if ($tamanho > 0) {
        $amostra = substr($linha, 0, min(50, $tamanho));
        echo "├─ Primeiros 50 chars: ";
        for ($j = 0; $j < strlen($amostra); $j++) {
            $char = $amostra[$j];
            echo ($char === ' ' ? '·' : $char);
        }
        echo "\n";
        echo "└─ Posições: ";
        for ($j = 0; $j < strlen($amostra); $j++) {
            echo ($j % 10);
        }
        echo "\n\n";
    }
}

// Tentar identificar padrão dos registros
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "ANÁLISE DE PADRÃO:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Pular cabeçalhos (linhas 0, 1, 2)
$inicio_dados = 4;

echo "Tentando encontrar NUPATRIMONIO nas primeiras linhas de dados:\n\n";

for ($i = $inicio_dados; $i < min($inicio_dados + 10, count($linhas)); $i++) {
    $linha = $linhas[$i];
    
    // NUPATRIMONIO está no início (até 16 caracteres)
    $nupatrimonio = trim(substr($linha, 0, 16));
    
    echo "Linha $i:\n";
    echo "  NUPATRIMONIO (0-16): '$nupatrimonio'\n";
    
    // Verificar se é número
    if (is_numeric($nupatrimonio)) {
        echo "  ✅ É numérico!\n";
        
        // Tentar extrair outros campos
        $situacao = trim(substr($linha, 16, 35));
        $marca = trim(substr($linha, 51, 35));
        $cdlocal = trim(substr($linha, 86, 11));
        
        echo "  SITUACAO (16-51): '$situacao'\n";
        echo "  MARCA (51-86): '$marca'\n";
        echo "  CDLOCAL (86-97): '$cdlocal'\n";
    } else {
        echo "  ⚠️  Não é numérico - pode ser cabeçalho/separador\n";
    }
    echo "\n";
}

// Análise de linhas vazias
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "LINHAS VAZIAS OU MUITO CURTAS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$vazias = 0;
for ($i = 0; $i < count($linhas); $i++) {
    if (strlen(trim($linhas[$i])) < 10) {
        $vazias++;
        if ($vazias <= 5) {
            echo "Linha $i: tamanho " . strlen($linhas[$i]) . "\n";
        }
    }
}
echo "\nTotal de linhas vazias/curtas: $vazias\n";

echo "\n✅ Análise concluída!\n";
