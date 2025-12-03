<?php
/**
 * TESTE DE ATUALIZAÇÃO - Importa apenas patrimônios 1-50 para validar
 */

$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TESTE DE ATUALIZAÇÃO (primeiros 50 registros)           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Arquivo NOVO
$arquivo = '/home/plansul/www/estoque-laravel/Patrimonio_NOVO.TXT';

if (!file_exists($arquivo)) {
    die("❌ Arquivo não encontrado: $arquivo\n");
}

$lines = file($arquivo, FILE_IGNORE_NEW_LINES);
echo "✅ Arquivo carregado: " . count($lines) . " linhas\n\n";

// Verificar estado ANTES
echo "📊 ESTADO ANTES DA IMPORTAÇÃO:\n";
echo "════════════════════════════════════════════════════════════\n";
$check = $pdo->query("SELECT NUPATRIMONIO, SITUACAO, USUARIO, CDPROJETO FROM patr WHERE NUPATRIMONIO IN (3, 38, 45, 100) ORDER BY NUPATRIMONIO");
while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
    printf("  #%-3s | SITUACAO: %-15s | USUARIO: %-10s | CDPROJETO: %s\n", 
        $row['NUPATRIMONIO'], 
        $row['SITUACAO'] ?: '(vazio)', 
        $row['USUARIO'] ?: '(vazio)',
        $row['CDPROJETO'] ?: '(vazio)'
    );
}
echo "\n";

// Processar apenas primeiros 50 registros
echo "🔄 IMPORTANDO (primeiros 50 registros)...\n";
echo "════════════════════════════════════════════════════════════\n";

$pdo->beginTransaction();
$created = $updated = $errors = 0;

for ($i = 2; $i < min(52, count($lines)); $i++) { // Linhas 2-51 (50 registros)
    $line = $lines[$i];
    
    if (strlen(trim($line)) < 10 || strpos($line, '===') !== false) {
        continue;
    }
    
    if (!mb_check_encoding($line, 'UTF-8')) {
        $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
    }
    
    $nupatrimonio = trim(substr($line, 0, 16));
    
    if (!is_numeric($nupatrimonio)) {
        continue;
    }
    
    $situacao = trim(substr($line, 16, 35));
    $marca = trim(substr($line, 51, 35));
    $cdlocal = trim(substr($line, 86, 11));
    $modelo = trim(substr($line, 97, 35));
    $cor = trim(substr($line, 132, 20));
    $dtaquisicao_raw = trim(substr($line, 152, 11));
    $depatrimonio = trim(substr($line, 163, 285));
    $cdfunc = trim(substr($line, 448, 18));
    $cdprojeto = trim(substr($line, 466, 13));
    $nudocfiscal = trim(substr($line, 479, 15));
    $usuario = trim(substr($line, 494, 15));
    $dtoperacao = trim(substr($line, 509, 14));
    $numof = trim(substr($line, 523, 10));
    $cdobjeto = trim(substr($line, 533, 13));
    
    // Substituir <null>
    $situacao = ($situacao === '<null>') ? '' : $situacao;
    $marca = ($marca === '<null>') ? '' : $marca;
    $cor = ($cor === '<null>') ? '' : $cor;
    $cdobjeto = ($cdobjeto === '<null>') ? '' : $cdobjeto;
    $usuario = ($usuario === '<null>' || empty($usuario)) ? 'SISTEMA' : $usuario;
    
    // Normalizar data
    $dtaquisicao = $dtaquisicao_raw;
    if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
        $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    
    if (empty($nupatrimonio) || !is_numeric($nupatrimonio)) continue;
    
    // Verificar se já existe
    $checkStmt = $pdo->prepare("SELECT NUSEQPATR FROM patr WHERE NUPATRIMONIO = ? LIMIT 1");
    $checkStmt->execute([$nupatrimonio]);
    $exists = $checkStmt->fetch();
    
    try {
        if ($exists) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE patr SET
                    DEPATRIMONIO = ?,
                    SITUACAO = ?,
                    MARCA = ?,
                    MODELO = ?,
                    COR = ?,
                    CDLOCAL = ?,
                    CDMATRFUNCIONARIO = ?,
                    CDPROJETO = ?,
                    CODOBJETO = ?,
                    USUARIO = ?,
                    DTAQUISICAO = ?
                WHERE NUPATRIMONIO = ?
            ");
            $stmt->execute([
                $depatrimonio, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $cdobjeto, $usuario, $dtaquisicao,
                $nupatrimonio
            ]);
            if ($stmt->rowCount() > 0) {
                $updated++;
                if (in_array($nupatrimonio, [3, 38, 45, 100])) {
                    echo "  ✅ Patrimônio #$nupatrimonio ATUALIZADO\n";
                }
            }
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO patr (
                    NUPATRIMONIO, DEPATRIMONIO, SITUACAO, MARCA, MODELO, COR,
                    CDLOCAL, CDMATRFUNCIONARIO, CDPROJETO, CODOBJETO, USUARIO,
                    DTAQUISICAO
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nupatrimonio, $depatrimonio, $situacao, $marca, $modelo, $cor,
                $cdlocal, $cdfunc, $cdprojeto, $cdobjeto, $usuario, $dtaquisicao
            ]);
            $created++;
        }
    } catch (Exception $e) {
        $errors++;
        echo "  ⚠️  Erro patrimônio $nupatrimonio: " . substr($e->getMessage(), 0, 60) . "\n";
    }
}

$pdo->commit();

echo "\n📊 RESULTADO DO TESTE:\n";
echo "════════════════════════════════════════════════════════════\n";
echo "  Novos: $created\n";
echo "  Atualizados: $updated\n";
echo "  Erros: $errors\n\n";

// Verificar estado DEPOIS
echo "📊 ESTADO DEPOIS DA IMPORTAÇÃO:\n";
echo "════════════════════════════════════════════════════════════\n";
$check = $pdo->query("SELECT NUPATRIMONIO, SITUACAO, USUARIO, CDPROJETO FROM patr WHERE NUPATRIMONIO IN (3, 38, 45, 100) ORDER BY NUPATRIMONIO");
while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
    printf("  #%-3s | SITUACAO: %-15s | USUARIO: %-10s | CDPROJETO: %s\n", 
        $row['NUPATRIMONIO'], 
        $row['SITUACAO'] ?: '(vazio)', 
        $row['USUARIO'] ?: '(vazio)',
        $row['CDPROJETO'] ?: '(vazio)'
    );
}

echo "\n✅ Teste concluído!\n";
