<?php
/**
 * RESTAURAR registros corretos que foram deletados por engano
 * Vamos reimportar apenas os patrimônios #3, #38, #45 do arquivo novo
 */

$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🔧 RESTAURAÇÃO DOS REGISTROS CORRETOS\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Estado ANTES
echo "📊 ESTADO ANTES (registros que ficaram após limpeza ERRADA):\n";
$check = $pdo->query("SELECT NUPATRIMONIO, SITUACAO, USUARIO, CDPROJETO FROM patr WHERE NUPATRIMONIO IN (3, 38, 45) ORDER BY NUPATRIMONIO");
while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
    printf("  #%-3s | SITUACAO: %-15s | USUARIO: %-10s | CDPROJETO: %s\n", 
        $row['NUPATRIMONIO'], 
        $row['SITUACAO'] ?: '(vazio)', 
        $row['USUARIO'],
        $row['CDPROJETO']
    );
}
echo "\n";

// Arquivo NOVO
$arquivo = '/home/plansul/www/estoque-laravel/Patrimonio_NOVO.TXT';
$lines = file($arquivo, FILE_IGNORE_NEW_LINES);

echo "🔄 ATUALIZANDO com dados corretos do arquivo novo...\n\n";

// Processar apenas patrimônios 3, 38, 45
$targets = [3, 38, 45];
$updated = 0;

for ($i = 2; $i < count($lines); $i++) {
    $line = $lines[$i];
    
    if (strlen(trim($line)) < 10 || strpos($line, '===') !== false) {
        continue;
    }
    
    if (!mb_check_encoding($line, 'UTF-8')) {
        $line = iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $line);
    }
    
    $nupatrimonio = trim(substr($line, 0, 16));
    
    if (!is_numeric($nupatrimonio) || !in_array($nupatrimonio, $targets)) {
        continue;
    }
    
    // Extrair dados
    $situacao = trim(substr($line, 16, 35));
    $marca = trim(substr($line, 51, 35));
    $cdlocal = trim(substr($line, 86, 11));
    $modelo = trim(substr($line, 97, 35));
    $cor = trim(substr($line, 132, 20));
    $dtaquisicao_raw = trim(substr($line, 152, 11));
    $depatrimonio = trim(substr($line, 163, 285));
    $cdfunc = trim(substr($line, 448, 18));
    $cdprojeto = trim(substr($line, 466, 13));
    $usuario = trim(substr($line, 494, 15));
    $cdobjeto = trim(substr($line, 533, 13));
    
    // Substituir <null>
    $situacao = ($situacao === '<null>') ? '' : $situacao;
    $marca = ($marca === '<null>') ? '' : $marca;
    $cor = ($cor === '<null>') ? '' : $cor;
    $cdobjeto = ($cdobjeto === '<null>') ? '' : $cdobjeto;
    $usuario = ($usuario === '<null>' || empty($usuario)) ? 'SISTEMA' : $usuario;
    
    // Normalizar data
    $dtaquisicao = null;
    if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
        $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    
    // UPDATE forçado
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
    
    echo "  ✅ Patrimônio #$nupatrimonio atualizado: SITUACAO='$situacao', CDPROJETO=$cdprojeto\n";
    $updated++;
}

echo "\n📊 ESTADO DEPOIS DA RESTAURAÇÃO:\n";
$check = $pdo->query("SELECT NUPATRIMONIO, SITUACAO, USUARIO, CDPROJETO FROM patr WHERE NUPATRIMONIO IN (3, 38, 45) ORDER BY NUPATRIMONIO");
while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
    printf("  #%-3s | SITUACAO: %-15s | USUARIO: %-10s | CDPROJETO: %s\n", 
        $row['NUPATRIMONIO'], 
        $row['SITUACAO'] ?: '(vazio)', 
        $row['USUARIO'],
        $row['CDPROJETO']
    );
}

echo "\n✅ Restauração concluída! $updated registros atualizados\n";
