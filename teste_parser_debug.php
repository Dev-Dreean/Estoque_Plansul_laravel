<?php
// One-off script - Testar parser com exato conteúdo do Power Automate

$htmlBody = <<<'HTML'
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div dir="ltr"><div class="gmail_default" style="font-family:comic sans ms,sans-serif">Solicitante: João Silva Teste<br>Matricula: 99999<br>Projeto: 1234 - Sistema Plansul<br>UF: SP<br>Setor: TI<br>Local destino: Sala de Testes<br>Observacao: Teste final da integração<br><br>Itens:<br>- Monitor 24"; 1; UN; Teste monitor<br>- Mouse; 1; UN; Teste mouse</div></div></body></html>
HTML;

// Simular extractBody
function extractBody($htmlBody) {
    $body = $htmlBody;
    
    $body = trim($body); 
    if ($body === '') {
        return '';
    }

    $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    echo "=== Após html_entity_decode ===\n";
    echo $body . "\n\n";
    
    // Converter <br>, <br/>, <br /> em quebras de linha ANTES de remover tags
    $body = preg_replace('/<br\s*\/?>/i', "\n", $body);
    echo "=== Após converter <br> em newlines ===\n";
    echo $body . "\n\n";
    
    // Remover tags HTML
    $body = strip_tags($body);
    echo "=== Após strip_tags ===\n";
    echo $body . "\n\n";
    
    // Normalizar quebras de linha
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    
    // Remover múltiplas quebras de linha consecutivas
    $body = preg_replace('/\n+/', "\n", $body);
    
    echo "=== Resultado final ===\n";
    echo $body . "\n";
    echo "---\n";
    
    return trim($body);
}

$extracted = extractBody($htmlBody);
echo "\n\n=== PARSEANDO... ===\n";

// Agora simular o parser
$lines = preg_split('/\n+/', $extracted);
echo "Total de linhas: " . count($lines) . "\n";
foreach ($lines as $i => $line) {
    echo "[$i] " . $line . "\n";
}

// Tentar extrair campo "Solicitante"
echo "\n\n=== TENTANDO EXTRAIR CAMPOS ===\n";
foreach ($lines as $line) {
    if (preg_match('/^(.+?)\s*[:=]\s*(.*)$/', $line, $matches)) {
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        echo "KEY: '$key' => VALUE: '$value'\n";
        
        // Normalizar a chave
        $normalized = mb_strtolower($key, 'UTF-8');
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
        $normalized = trim($normalized);
        echo "  NORMALIZED: '$normalized'\n";
    }
}

echo "\n✅ Fim do teste\n";
?>
