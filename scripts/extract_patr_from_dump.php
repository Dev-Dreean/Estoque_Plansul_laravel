<?php
/**
 * Script para extrair NUPATRIMONIO, USUARIO, CDMATRFUNCIONARIO do dump SQL
 * e gerar CSV + UPDATE SQL pronto para importar no servidor KingHost.
 */

$dumpFile = __DIR__ . '/../storage/app/patrimonios_dump.sql';
$csvOutput = __DIR__ . '/../storage/output/patr_export.csv';
$sqlOutput = __DIR__ . '/../storage/output/patr_updates.sql';

// Criar diretório de output se não existir
@mkdir(dirname($csvOutput), 0755, true);

if (!file_exists($dumpFile)) {
    die("Erro: arquivo de dump não encontrado: $dumpFile\n");
}

echo "[1] Lendo dump SQL...\n";
$dumpContent = file_get_contents($dumpFile);

// Regex para extrair INSERT INTO patr VALUES (...)
$pattern = "/INSERT INTO patr VALUES \('([^']*)',\'([^']*)',\'([^']*)',([^,]*),\'([^']*)',\'([^']*)',([^,]*),([^,]*),\'([^']*)',([^,]*),\'([^']*)',\'([^']*)',([^,]*),([^,]*),\'([^']*)',([^,]*),\'([^']*)',\'([^']*)',([^,]*),\'([^']*)',([^,]*),([^,]*),([^,]*),\'([^']*)',([^,]*),\'([^']*)'[^)]*\);/";

$matches = [];
$count = preg_match_all($pattern, $dumpContent, $matches);

if ($count === 0) {
    echo "Aviso: nenhum match encontrado com regex. Tentando parsing simplificado...\n";
    // Fallback: processar linha por linha
    $lines = explode("\n", $dumpContent);
    $patrimonios = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'INSERT INTO patr VALUES') === 0) {
            // Remover 'INSERT INTO patr VALUES (' e ');
            $line = substr($line, strlen('INSERT INTO patr VALUES ('));
            $line = substr($line, 0, -2); // Remove ");
            
            // Processar valores (cuidado com strings quoted)
            // Este é um parser simplificado; para casos complexos, use regex com lookahead
            $values = [];
            $currentValue = '';
            $inQuote = false;
            
            for ($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];
                
                if ($char === "'" && ($i === 0 || $line[$i-1] !== '\\')) {
                    $inQuote = !$inQuote;
                } elseif ($char === ',' && !$inQuote) {
                    $values[] = trim($currentValue, "'");
                    $currentValue = '';
                } else {
                    $currentValue .= $char;
                }
            }
            // Último valor
            if ($currentValue !== '') {
                $values[] = trim($currentValue, "'");
            }
            
            if (count($values) >= 21) {
                // Mapa: índices esperados do SQL
                // NUSEQPATR=0, NUPATRIMONIO=1, SITUACAO=2, TIPO=3, MARCA=4, MODELO=5, ...
                $nuseqpatr = $values[0];
                $nupatrimonio = $values[1];
                $cdmatrfuncionario = isset($values[16]) ? $values[16] : '';
                $usuario = isset($values[19]) ? $values[19] : '';
                
                $patrimonios[] = [
                    'NUSEQPATR' => $nuseqpatr,
                    'NUPATRIMONIO' => $nupatrimonio,
                    'CDMATRFUNCIONARIO' => $cdmatrfuncionario,
                    'USUARIO' => $usuario,
                ];
            }
        }
    }
    $count = count($patrimonios);
    echo "[2] Processados $count registros via parsing simplificado.\n";
} else {
    // Regex match bem-sucedido
    echo "[2] Encontrados $count registros via regex.\n";
    $patrimonios = [];
    
    for ($i = 0; $i < $count; $i++) {
        // Índices do match:
        // $matches[0] = full match
        // $matches[1] = primeiro value (NUSEQPATR)
        // $matches[2] = NUPATRIMONIO
        // ... etc
        // Para este padrão, precisamos contar os grupos
        
        // Simplificado: usar a string full e re-parsear se necessário
        // Ou usar matches diretos. Aqui usamos um approach direto:
        
        // Reparse cada linha completa
        $fullMatch = $matches[0][$i];
        $fullMatch = substr($fullMatch, strlen('INSERT INTO patr VALUES ('));
        $fullMatch = substr($fullMatch, 0, -2); // Remove ");
        
        $values = [];
        $currentValue = '';
        $inQuote = false;
        
        for ($j = 0; $j < strlen($fullMatch); $j++) {
            $char = $fullMatch[$j];
            
            if ($char === "'" && ($j === 0 || $fullMatch[$j-1] !== '\\')) {
                $inQuote = !$inQuote;
            } elseif ($char === ',' && !$inQuote) {
                $values[] = trim($currentValue, "'");
                $currentValue = '';
            } else {
                $currentValue .= $char;
            }
        }
        if ($currentValue !== '') {
            $values[] = trim($currentValue, "'");
        }
        
        if (count($values) >= 21) {
            $nuseqpatr = $values[0];
            $nupatrimonio = $values[1];
            $cdmatrfuncionario = isset($values[16]) ? $values[16] : '';
            $usuario = isset($values[19]) ? $values[19] : '';
            
            $patrimonios[] = [
                'NUSEQPATR' => $nuseqpatr,
                'NUPATRIMONIO' => $nupatrimonio,
                'CDMATRFUNCIONARIO' => $cdmatrfuncionario,
                'USUARIO' => $usuario,
            ];
        }
    }
}

echo "[3] Filtrando registros válidos (USUARIO não vazio)...\n";
$patrimoniosValidos = array_filter($patrimonios, function ($p) {
    return !empty($p['USUARIO']) && strtoupper(trim($p['USUARIO'])) !== 'SISTEMA';
});

echo "[4] Total válidos: " . count($patrimoniosValidos) . "\n";

// Gerar CSV
echo "[5] Gerando CSV ($csvOutput)...\n";
$csvHandle = fopen($csvOutput, 'w');
if (!$csvHandle) {
    die("Erro ao abrir $csvOutput para escrita.\n");
}

// Header
fputcsv($csvHandle, ['NUSEQPATR', 'NUPATRIMONIO', 'CDMATRFUNCIONARIO', 'USUARIO']);

// Dados
foreach ($patrimoniosValidos as $p) {
    fputcsv($csvHandle, [
        $p['NUSEQPATR'],
        $p['NUPATRIMONIO'],
        $p['CDMATRFUNCIONARIO'],
        $p['USUARIO'],
    ]);
}

fclose($csvHandle);
echo "✓ CSV gerado com sucesso: $csvOutput\n";

// Gerar SQL UPDATE (em lotes de 100 linhas)
echo "[6] Gerando SQL UPDATE ($sqlOutput)...\n";
$sqlHandle = fopen($sqlOutput, 'w');
if (!$sqlHandle) {
    die("Erro ao abrir $sqlOutput para escrita.\n");
}

fwrite($sqlHandle, "-- Arquivo de UPDATE para patr.USUARIO\n");
fwrite($sqlHandle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($sqlHandle, "-- Total de registros: " . count($patrimoniosValidos) . "\n\n");
fwrite($sqlHandle, "USE plansul04;\n\n");

// Backup antes de atualizar
fwrite($sqlHandle, "-- Criar backup da tabela patr antes de atualizar\n");
fwrite($sqlHandle, "CREATE TABLE IF NOT EXISTS patr_backup_after_import AS SELECT * FROM patr;\n\n");

// Transação
fwrite($sqlHandle, "START TRANSACTION;\n\n");

$batch = [];
$batchSize = 100;
$totalBatches = ceil(count($patrimoniosValidos) / $batchSize);

foreach ($patrimoniosValidos as $index => $p) {
    $nupatrimonio = $p['NUPATRIMONIO'];
    $usuario = addslashes($p['USUARIO']);
    
    // UPDATE com NUPATRIMONIO como chave
    fwrite($sqlHandle, "UPDATE patr SET USUARIO = '$usuario' WHERE NUPATRIMONIO = '$nupatrimonio';\n");
    
    // Breakpoint a cada 100 linhas
    if (($index + 1) % 100 === 0) {
        $currentBatch = intval(($index + 1) / 100);
        fwrite($sqlHandle, "-- Lote $currentBatch/$totalBatches concluído\n\n");
    }
}

fwrite($sqlHandle, "\nCOMMIT;\n\n");

// Query de validação
fwrite($sqlHandle, "-- Validação: verificar quantos registros ainda estão 'SISTEMA' ou NULL\n");
fwrite($sqlHandle, "SELECT COUNT(*) AS restantes_problema FROM patr p\n");
fwrite($sqlHandle, "LEFT JOIN usuario u ON p.USUARIO = u.NMLOGIN\n");
fwrite($sqlHandle, "WHERE p.USUARIO IS NULL\n");
fwrite($sqlHandle, "   OR TRIM(p.USUARIO) = ''\n");
fwrite($sqlHandle, "   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'\n");
fwrite($sqlHandle, "   OR u.NUSEQUSUARIO IS NULL;\n");

fclose($sqlHandle);
echo "✓ SQL UPDATE gerado com sucesso: $sqlOutput\n";

echo "\n=== RESUMO ===\n";
echo "CSV: $csvOutput\n";
echo "SQL: $sqlOutput\n";
echo "Total de patrimônios a atualizar: " . count($patrimoniosValidos) . "\n";
echo "\nPróximas etapas:\n";
echo "1. Baixe $csvOutput ou $sqlOutput\n";
echo "2. No phpMyAdmin do KingHost, execute o SQL ou importe o CSV\n";
echo "3. Execute: php artisan cache:clear\n";
echo "4. Teste a UI dos patrimônios\n";
