<?php
/**
 * Script para extrair TODAS as colunas de `patr` do dump local
 * e gerar DELETE + INSERT completos com USUARIO correto (sem " (PRE)")
 * 
 * Uso: php scripts/extract_patr_complete.php
 */

$dumpFile = __DIR__ . '/../storage/app/patrimonios_dump.sql';
$sqlOutput = __DIR__ . '/../storage/output/patr_complete_reimport.sql';

@mkdir(dirname($sqlOutput), 0755, true);

if (!file_exists($dumpFile)) {
    die("Erro: arquivo de dump não encontrado: $dumpFile\n");
}

echo "[1] Lendo dump SQL completo...\n";
$dumpContent = file_get_contents($dumpFile);

// Extrair todas as linhas INSERT INTO patr
$lines = explode("\n", $dumpContent);
$patrimonios = [];

foreach ($lines as $line) {
    if (strpos($line, 'INSERT INTO patr VALUES') === 0) {
        // Remover 'INSERT INTO patr VALUES (' e ');
        $line = substr($line, strlen('INSERT INTO patr VALUES ('));
        $line = substr($line, 0, -2); // Remove ");
        
        // Parser ROBUSTO para extrair valores com escape corrigido
        // O dump usa escapes com barra invertida: \' = aspa simples
        $values = [];
        $i = 0;
        $len = strlen($line);
        
        while ($i < $len) {
            // Pular espaços antes de um valor
            while ($i < $len && $line[$i] === ' ') {
                $i++;
            }
            
            if ($i >= $len) break;
            
            // Verificar se é NULL
            if ($i + 4 <= $len && substr($line, $i, 4) === 'NULL') {
                $values[] = '';
                $i += 4;
                // Pular a vírgula se houver
                while ($i < $len && ($line[$i] === ',' || $line[$i] === ' ')) {
                    $i++;
                }
            }
            // Verificar se começa com aspa simples
            elseif ($line[$i] === "'") {
                $i++; // Pular aspa inicial
                $value = '';
                
                // Ler até encontrar aspa não escapada
                while ($i < $len) {
                    if ($line[$i] === '\\' && $i + 1 < $len && $line[$i + 1] === "'") {
                        // Escape: \' = aspa simples no valor
                        $value .= "'";
                        $i += 2;
                    } elseif ($line[$i] === "'") {
                        // Aspa não escapada = fim da string
                        $i++;
                        break;
                    } else {
                        $value .= $line[$i];
                        $i++;
                    }
                }
                
                $values[] = $value;
                
                // Pular a vírgula se houver
                while ($i < $len && ($line[$i] === ',' || $line[$i] === ' ')) {
                    $i++;
                }
            }
            else {
                // Valor não reconhecido, pular até próxima vírgula ou fim
                $i++;
            }
        }
        
        if (count($values) >= 21) {
            // Índices esperados (baseado no dump):
            // 0=NUSEQPATR, 1=NUPATRIMONIO, 2=SITUACAO, 3=TIPO, 4=MARCA, 5=MODELO, 
            // 6=CARACTERISTICAS, 7=DIMENSAO, 8=COR, 9=NUSERIE, 10=CDLOCAL, 
            // 11=DTAQUISICAO, 12=DTBAIXA, 13=DTGARANTIA, 14=DEHISTORICO, 
            // 15=DTLAUDO, 16=DEPATRIMONIO, 17=CDMATRFUNCIONARIO, 18=CDLOCALINTERNO, 
            // 19=CDPROJETO, 20=USUARIO, 21=DTOPERACAO, 22=FLCONFERIDO, 23=NUMOF, 24=CODOBJETO, 25=NMPLANTA
            
            // Limpar " (PRE)" do USUARIO (índice 20)
            $usuario = isset($values[20]) ? $values[20] : '';
            $usuario = str_replace(' (PRE)', '', $usuario);
            $usuario = trim($usuario);
            
            $patrimonios[] = [
                'NUSEQPATR' => $values[0],
                'NUPATRIMONIO' => $values[1],
                'SITUACAO' => isset($values[2]) ? $values[2] : '',
                'TIPO' => isset($values[3]) ? $values[3] : '',
                'MARCA' => isset($values[4]) ? $values[4] : '',
                'MODELO' => isset($values[5]) ? $values[5] : '',
                'CARACTERISTICAS' => isset($values[6]) ? $values[6] : '',
                'DIMENSAO' => isset($values[7]) ? $values[7] : '',
                'COR' => isset($values[8]) ? $values[8] : '',
                'NUSERIE' => isset($values[9]) ? $values[9] : '',
                'CDLOCAL' => isset($values[10]) ? $values[10] : '',
                'DTAQUISICAO' => isset($values[11]) ? $values[11] : '',
                'DTBAIXA' => isset($values[12]) ? $values[12] : '',
                'DTGARANTIA' => isset($values[13]) ? $values[13] : '',
                'DEHISTORICO' => isset($values[14]) ? $values[14] : '',
                'DTLAUDO' => isset($values[15]) ? $values[15] : '',
                'DEPATRIMONIO' => isset($values[16]) ? $values[16] : '',
                'CDMATRFUNCIONARIO' => isset($values[17]) ? $values[17] : '',
                'CDLOCALINTERNO' => isset($values[18]) ? $values[18] : '',
                'CDPROJETO' => isset($values[19]) ? $values[19] : '',
                'USUARIO' => $usuario,
                'DTOPERACAO' => isset($values[21]) ? $values[21] : '',
                'FLCONFERIDO' => isset($values[22]) ? $values[22] : '',
                'NUMOF' => isset($values[23]) ? $values[23] : '',
                'CODOBJETO' => isset($values[24]) ? $values[24] : '',
                'NMPLANTA' => isset($values[25]) ? $values[25] : '',
            ];
        }
    }
}

echo "[2] Total de registros extraídos: " . count($patrimonios) . "\n";

// Gerar SQL com DELETE + INSERT
echo "[3] Gerando SQL de DELETE + INSERT completo ($sqlOutput)...\n";
$sqlHandle = fopen($sqlOutput, 'w');
if (!$sqlHandle) {
    die("Erro ao abrir $sqlOutput para escrita.\n");
}

fwrite($sqlHandle, "-- Reimportação completa de patr com USUARIO correto\n");
fwrite($sqlHandle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($sqlHandle, "-- Total de registros: " . count($patrimonios) . "\n\n");
fwrite($sqlHandle, "USE plansul04;\n\n");

// Backup
fwrite($sqlHandle, "-- Backup da tabela patr antes de deletar\n");
fwrite($sqlHandle, "CREATE TABLE IF NOT EXISTS patr_backup_before_reimport AS SELECT * FROM patr;\n\n");

// Transação
fwrite($sqlHandle, "START TRANSACTION;\n\n");

// Delete
fwrite($sqlHandle, "-- Deletar todos os patrimônios (será reimportado completo)\n");
fwrite($sqlHandle, "DELETE FROM patr;\n\n");

// Inserts
fwrite($sqlHandle, "-- Reimportar patrimônios com dados completos + USUARIO correto\n");

$batch = 0;
$batchSize = 50;

foreach ($patrimonios as $index => $p) {
    // Escaper seguro
    $cols = [
        'NUSEQPATR', 'NUPATRIMONIO', 'SITUACAO', 'TIPO', 'MARCA', 'MODELO',
        'CARACTERISTICAS', 'DIMENSAO', 'COR', 'NUSERIE', 'CDLOCAL',
        'DTAQUISICAO', 'DTBAIXA', 'DTGARANTIA', 'DEHISTORICO',
        'DTLAUDO', 'DEPATRIMONIO', 'CDMATRFUNCIONARIO', 'CDLOCALINTERNO',
        'CDPROJETO', 'USUARIO', 'DTOPERACAO', 'FLCONFERIDO', 'NUMOF', 'CODOBJETO', 'NMPLANTA'
    ];
    
    $vals = [];
    foreach ($cols as $col) {
        $val = $p[$col] ?? '';
        
        // Se NULL ou 'NULL', manter NULL; senão, escapar e cotar
        if ($val === '' || $val === 'NULL' || is_null($val)) {
            $vals[] = 'NULL';
        } else {
            // Escape
            $val = str_replace("'", "''", $val);
            $vals[] = "'$val'";
        }
    }
    
    $colList = implode(', ', $cols);
    $valList = implode(', ', $vals);
    
    fwrite($sqlHandle, "INSERT INTO patr ($colList) VALUES ($valList);\n");
    
    // Breakpoint a cada 50 linhas
    if (($index + 1) % $batchSize === 0) {
        $batch++;
        $totalBatches = ceil(count($patrimonios) / $batchSize);
        fwrite($sqlHandle, "-- Lote $batch/$totalBatches concluído\n");
    }
}

fwrite($sqlHandle, "\nCOMMIT;\n\n");

// Validação
fwrite($sqlHandle, "-- Contagem final\n");
fwrite($sqlHandle, "SELECT COUNT(*) AS total_patrimonios FROM patr;\n\n");

// Query para verificar ainda problemáticos (deve retornar 0 ou perto disso)
fwrite($sqlHandle, "-- Verificação: quantos ainda têm USUARIO problemático?\n");
fwrite($sqlHandle, "SELECT COUNT(*) AS problematicos FROM patr p\n");
fwrite($sqlHandle, "LEFT JOIN usuario u ON p.USUARIO = u.NMLOGIN\n");
fwrite($sqlHandle, "WHERE p.USUARIO IS NULL\n");
fwrite($sqlHandle, "   OR TRIM(p.USUARIO) = ''\n");
fwrite($sqlHandle, "   OR TRIM(UPPER(p.USUARIO)) = 'SISTEMA'\n");
fwrite($sqlHandle, "   OR u.NUSEQUSUARIO IS NULL;\n");

fclose($sqlHandle);
echo "✓ SQL completo gerado com sucesso: $sqlOutput\n";

echo "\n=== RESUMO ===\n";
echo "Total de patrimônios a reimportar: " . count($patrimonios) . "\n";
echo "Arquivo gerado: $sqlOutput\n";
echo "\nPróximas etapas:\n";
echo "1. Faça backup da tabela patr no servidor (será criado automaticamente)\n";
echo "2. Copie TODO o conteúdo de $sqlOutput\n";
echo "3. No phpMyAdmin do KingHost:\n";
echo "   - Vá até SQL\n";
echo "   - Cole o SQL\n";
echo "   - Execute\n";
echo "4. Após sucesso, execute: php artisan cache:clear\n";
