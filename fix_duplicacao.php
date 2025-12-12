<?php
// one-off: Remover código duplicado do PatrimonioController

$file = 'app/Http/Controllers/PatrimonioController.php';
$content = file_get_contents($file);

// Padrão do código duplicado (entre linhas 1391-1406)
$pattern = '/\n\s+return response\(\)->json\(\[\n\s+\'id\' => \$local->id,\n\s+\'cdlocal\' => \$local->cdlocal,\n\s+\'delocal\' => \$local->delocal,\n\s+\'LOCAL\' => \$local->delocal,\n\s+\'CDPROJETO\' => \$local->projeto\?\-\>CDPROJETO,\n\s+\'NOMEPROJETO\' => \$local->projeto\?\-\>NOMEPROJETO,\n\s+\'tabfant_id\' => \$local->tabfant_id,\n\s+\'flativo\' => \$local->flativo \?\? true,\n\s+\]\);\n\s+\} catch \(\\\Throwable \$e\) \{\n\s+Log::error\(\'Erro ao buscar local por ID:\', \[\'id\' => \$id, \'erro\' => \$e->getMessage\(\)\]\);\n\s+return response\(\)->json\(\[\'error\' => \'Erro ao buscar local\'\], 500\);\n\s+\}\n\s+\}/';

$replacement = '';

$newContent = preg_replace($pattern, $replacement, $content, 1);

if ($newContent && $newContent !== $content) {
    file_put_contents($file, $newContent);
    echo "✅ Código duplicado removido com sucesso\n";
} else {
    echo "❌ Não foi possível encontrar o padrão para remover\n";
    echo "Tentando abordagem alternativa...\n";
    
    // Encontrar e remover manualmente
    $lines = explode("\n", $content);
    $newLines = [];
    $skipUntil = 0;
    
    for ($i = 0; $i < count($lines); $i++) {
        if ($i < $skipUntil) {
            continue;
        }
        
        // Se encontrar a linha 1391 (inicio do código duplicado), pular até linha 1406
        if (strpos($lines[$i], 'return response()->json([') !== false && 
            $i > 1390 && $i < 1410 &&
            strpos($lines[$i+15] ?? '', '}') !== false) {
            // Pular as próximas 16 linhas (duplicação)
            $skipUntil = $i + 16;
            echo "Encontrado duplicado nas linhas " . ($i+1) . "-" . ($skipUntil) . "\n";
            continue;
        }
        
        $newLines[] = $lines[$i];
    }
    
    $newContent = implode("\n", $newLines);
    file_put_contents($file, $newContent);
    echo "✅ Arquivo corrigido\n";
}
