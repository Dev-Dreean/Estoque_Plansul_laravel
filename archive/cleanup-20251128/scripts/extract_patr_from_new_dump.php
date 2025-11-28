<?php
$dumpFile = __DIR__ . '/../storage/output/patr_new_dump.sql';
$sqlOutput = __DIR__ . '/../storage/output/patr_complete_reimport_updated.sql';

@mkdir(dirname($sqlOutput), 0755, true);

if (!file_exists($dumpFile)) {
    die("Erro: arquivo de dump não encontrado: $dumpFile\n");
}

echo "[1] Lendo dump SQL atualizado...\n";
$dumpContent = file_get_contents($dumpFile);
$lines = explode("\n", $dumpContent);
$patrimonios = [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    
    if (preg_match('/^\((.+)\)[,;]?\s*$/', $trimmed, $m)) {
        $valuesStr = $m[1];
        $values = [];
        $i = 0;
        $len = strlen($valuesStr);
        
        while ($i < $len) {
            while ($i < $len && ($valuesStr[$i] === ' ' || $valuesStr[$i] === ',')) {
                $i++;
            }
            
            if ($i >= $len) break;
            
            if ($i + 4 <= $len && substr($valuesStr, $i, 4) === 'NULL') {
                $values[] = '';
                $i += 4;
            }
            elseif ($valuesStr[$i] === "'") {
                $i++;
                $value = '';
                
                while ($i < $len) {
                    if ($valuesStr[$i] === '\\' && $i + 1 < $len && $valuesStr[$i + 1] === "'") {
                        $value .= "'";
                        $i += 2;
                    } elseif ($valuesStr[$i] === "'") {
                        $i++;
                        break;
                    } else {
                        $value .= $valuesStr[$i];
                        $i++;
                    }
                }
                
                $values[] = $value;
            }
            else {
                $i++;
            }
        }
        
        if (count($values) >= 26) {
            $patrimonios[] = $values;
        }
    }
}

echo "[2] Total de registros extraídos: " . count($patrimonios) . "\n";

echo "[3] Gerando SQL de DELETE + INSERT...\n";
$sqlHandle = fopen($sqlOutput, 'w');
if (!$sqlHandle) {
    die("Erro ao abrir arquivo.\n");
}

fwrite($sqlHandle, "-- Reimportação ATUALIZADA de patr com USUARIO correto\n");
fwrite($sqlHandle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($sqlHandle, "-- Total de registros: " . count($patrimonios) . "\n");
fwrite($sqlHandle, "-- Nota: Inclui usuários reais ligados aos patrimônios\n\n");

fwrite($sqlHandle, "USE plansul04;\n\n");
fwrite($sqlHandle, "CREATE TABLE IF NOT EXISTS patr_backup_before_reimport_updated AS SELECT * FROM patr;\n\n");
fwrite($sqlHandle, "START TRANSACTION;\n\n");
fwrite($sqlHandle, "DELETE FROM patr;\n\n");

$cols = [
    'NUSEQPATR', 'NUPATRIMONIO', 'SITUACAO', 'TIPO', 'MARCA', 'MODELO',
    'CARACTERISTICAS', 'DIMENSAO', 'COR', 'NUSERIE', 'CDLOCAL',
    'DTAQUISICAO', 'DTBAIXA', 'DTGARANTIA', 'DEHISTORICO',
    'DTLAUDO', 'DEPATRIMONIO', 'CDMATRFUNCIONARIO', 'CDLOCALINTERNO',
    'CDPROJETO', 'USUARIO', 'DTOPERACAO', 'FLCONFERIDO', 'NUMOF', 'CODOBJETO', 'NMPLANTA'
];

$batchSize = 50;

foreach ($patrimonios as $index => $row) {
    $vals = [];
    
    foreach ($cols as $idx => $col) {
        $val = isset($row[$idx]) ? $row[$idx] : '';
        
        if ($val === '' || $val === 'NULL' || is_null($val)) {
            $vals[] = 'NULL';
        } else {
            $val = str_replace("'", "''", $val);
            $vals[] = "'$val'";
        }
    }
    
    $colList = implode(', ', $cols);
    $valList = implode(', ', $vals);
    
    fwrite($sqlHandle, "INSERT INTO patr ($colList) VALUES ($valList);\n");
    
    if (($index + 1) % $batchSize === 0) {
        fwrite($sqlHandle, "-- Lote " . (($index + 1) / $batchSize) . " concluído\n");
    }
}

fwrite($sqlHandle, "\nCOMMIT;\n\n");
fwrite($sqlHandle, "SELECT COUNT(*) AS total_patrimonios FROM patr;\n");
fwrite($sqlHandle, "SELECT USUARIO, COUNT(*) as total FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' GROUP BY USUARIO ORDER BY total DESC;\n");

fclose($sqlHandle);
echo "✓ SQL gerado com sucesso: $sqlOutput\n";
echo "Total de patrimônios: " . count($patrimonios) . "\n";
