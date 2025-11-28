<?php
/**
 * Script para exportar dados ATUALIZADOS de patr do banco local
 * Captura todos os dados incluindo USUARIO correto (sem " (PRE)")
 * 
 * Uso: php scripts/export_patr_new_dump.php
 */

$host = '127.0.0.1';
$database = 'cadastros_plansul';
$user = 'root';
$password = '';
$port = 3306;

echo "[1] Conectando ao banco local ({$host}:{$port}/{$database})...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erro ao conectar: " . $e->getMessage() . "\n");
}

echo "[2] Exportando todos os dados de patr...\n";

// Buscar todos os registros com tratamento correto
$stmt = $pdo->prepare("SELECT * FROM patr ORDER BY NUSEQPATR ASC");
$stmt->execute();
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRegistros = count($registros);
echo "Total de registros encontrados: $totalRegistros\n";

// Preparar arquivo de dump
$dumpFile = __DIR__ . '/../storage/output/patr_new_dump.sql';
@mkdir(dirname($dumpFile), 0755, true);

$handle = fopen($dumpFile, 'w');
if (!$handle) {
    die("Erro ao criar arquivo: $dumpFile\n");
}

// Header
fwrite($handle, "-- Dump ATUALIZADO de patrimônios do banco local\n");
fwrite($handle, "-- Data: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Total de registros: $totalRegistros\n");
fwrite($handle, "-- Nota: Inclui USUARIO atualizado, sem suffix ' (PRE)'\n\n");

fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

// Gerar INSERTs com todas as colunas
$cols = [
    'NUSEQPATR', 'NUPATRIMONIO', 'SITUACAO', 'TIPO', 'MARCA', 'MODELO',
    'CARACTERISTICAS', 'DIMENSAO', 'COR', 'NUSERIE', 'CDLOCAL',
    'DTAQUISICAO', 'DTBAIXA', 'DTGARANTIA', 'DEHISTORICO',
    'DTLAUDO', 'DEPATRIMONIO', 'CDMATRFUNCIONARIO', 'CDLOCALINTERNO',
    'CDPROJETO', 'USUARIO', 'DTOPERACAO', 'FLCONFERIDO', 'NUMOF', 'CODOBJETO', 'NMPLANTA'
];

fwrite($handle, "INSERT INTO patr (" . implode(', ', $cols) . ") VALUES\n");

$batch = 0;
$batchSize = 50;
$batchCount = ceil($totalRegistros / $batchSize);

foreach ($registros as $index => $row) {
    $valores = [];
    
    foreach ($cols as $col) {
        $val = $row[$col] ?? null;
        
        // Limpar " (PRE)" do USUARIO
        if ($col === 'USUARIO' && $val) {
            $val = str_replace(' (PRE)', '', $val);
            $val = trim($val);
        }
        
        if ($val === null || $val === '') {
            $valores[] = 'NULL';
        } else {
            // Escape para SQL
            $val = addslashes($val);
            $valores[] = "'" . $val . "'";
        }
    }
    
    $valuesStr = implode(', ', $valores);
    
    if ($index < $totalRegistros - 1) {
        fwrite($handle, "($valuesStr),\n");
    } else {
        fwrite($handle, "($valuesStr);\n");
    }
    
    // Log de progresso
    if (($index + 1) % $batchSize === 0) {
        $batch++;
        $percent = round(($index + 1) / $totalRegistros * 100);
        echo "  [$batch/$batchCount] $percent% - " . ($index + 1) . "/$totalRegistros registros\n";
    }
}

fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n\n");

// Validação
fwrite($handle, "-- Validação\n");
fwrite($handle, "SELECT COUNT(*) AS total_patrimonios FROM patr;\n");

fclose($handle);

echo "\n✓ Dump atualizado gerado com sucesso: $dumpFile\n";
echo "Total de registros: $totalRegistros\n";
echo "\nPróximos passos:\n";
echo "1. Executar: php scripts/extract_patr_from_new_dump.php\n";
echo "2. Isso vai gerar: storage/output/patr_complete_reimport_updated.sql\n";
echo "3. Copiar para phpMyAdmin e executar no KingHost\n";
