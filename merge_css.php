<?php
/**
 * Script para mesclar o CSS antigo com os filtros novos
 * Copia toda a estrutura do arquivo antigo mas substitui a seção de filtros
 */

$antigo = file_get_contents('temp_old_index.blade.php');
$novo = file_get_contents('resources/views/patrimonios/index.blade.php');

// Extrair a seção de filtros do arquivo novo (linhas 36-156 aproximadamente)
// Procurar por: "{{-- Filtros (ACIMA dos botões) --}}" até "<div class="mt-6 flex justify-end space-x-4">"

$filtros_start = strpos($novo, '{{-- Filtros (ACIMA dos botões) --}}');
$filtros_end = strpos($novo, '<div class="mt-6 flex justify-end space-x-4">');

if ($filtros_start === false || $filtros_end === false) {
    echo "Erro: Não foi possível encontrar a seção de filtros no arquivo novo.\n";
    exit(1);
}

$filtros_novos = substr($novo, $filtros_start, $filtros_end - $filtros_start);

// Encontrar a seção de filtros no arquivo antigo para remover
$filtros_antigo_start = strpos($antigo, '{{-- Formul├írio de Filtro --}}');
$filtros_antigo_end = strpos($antigo, '<div class="mt-6 flex justify-end space-x-4">');

if ($filtros_antigo_start === false || $filtros_antigo_end === false) {
    echo "Erro: Não foi possível encontrar a seção de filtros no arquivo antigo.\n";
    exit(1);
}

// Montar o novo arquivo
$resultado = substr($antigo, 0, $filtros_antigo_start) . 
             $filtros_novos . 
             substr($antigo, $filtros_antigo_end);

// Salvar o resultado
file_put_contents('resources/views/patrimonios/index.blade.php', $resultado);

echo "✅ Merge realizado com sucesso!\n";
echo "CSS antigo com filtros novos aplicados.\n";
?>
