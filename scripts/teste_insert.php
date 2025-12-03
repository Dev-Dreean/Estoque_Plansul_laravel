<?php

// Script simplificado para testar APENAS o INSERT de patrimônios

$host = 'mysql07-farm10.kinghost.net';
$db = 'plansul04';
$user = 'plansul004_add2';
$pass = 'Pl@n2024';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado ao banco\n\n";
} catch (PDOException $e) {
    die("❌ Erro de conexão: " . $e->getMessage() . "\n");
}

echo "🧪 TESTE DE INSERT DIRETO\n\n";

// Pegar primeira linha válida do arquivo
$arquivo = __DIR__ . '/../patrimonio.TXT';
$lines = file($arquivo, FILE_IGNORE_NEW_LINES);

echo "Total linhas: " . count($lines) . "\n";
echo "Processando linha 2 (primeiro registro)...\n\n";

$line = $lines[2];

// Extrair dados
$nupatrimonio = trim(substr($line, 0, 16));
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
$usuario = ($usuario === '<null>' || empty($usuario)) ? 'SISTEMA' : $usuario;

// Normalizar data
$dtaquisicao = $dtaquisicao_raw;
if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dtaquisicao_raw, $m)) {
    $dtaquisicao = "{$m[3]}-{$m[2]}-{$m[1]}";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "DADOS EXTRAÍDOS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "NUPATRIMONIO:    '$nupatrimonio'\n";
echo "SITUACAO:        '$situacao'\n";
echo "MARCA:           '$marca'\n";
echo "CDLOCAL:         '$cdlocal'\n";
echo "MODELO:          '$modelo'\n";
echo "COR:             '$cor'\n";
echo "DTAQUISICAO:     '$dtaquisicao'\n";
echo "DEPATRIMONIO:    '" . substr($depatrimonio, 0, 50) . "...'\n";
echo "CDFUNC:          '$cdfunc'\n";
echo "CDPROJETO:       '$cdprojeto'\n";
echo "USUARIO:         '$usuario'\n";
echo "CDOBJETO:        '$cdobjeto'\n";
echo "\n";

// Validações
if (empty($nupatrimonio) || !is_numeric($nupatrimonio)) {
    die("❌ NUPATRIMONIO inválido: '$nupatrimonio'\n");
}

echo "✅ Validação passou: NUPATRIMONIO é numérico\n\n";

// Tentar INSERT
echo "Tentando INSERT...\n";

$sql = "
    INSERT INTO patr (
        NUPATRIMONIO, DEPATRIMONIO, SITUACAO, MARCA, MODELO, COR,
        CDLOCAL, CDFUNC, CDPROJETO, CDOBJETO, USUARIO,
        DTINCLUSAO, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        DEPATRIMONIO = VALUES(DEPATRIMONIO),
        SITUACAO = VALUES(SITUACAO),
        MARCA = VALUES(MARCA),
        MODELO = VALUES(MODELO),
        COR = VALUES(COR),
        CDLOCAL = VALUES(CDLOCAL),
        CDFUNC = VALUES(CDFUNC),
        CDPROJETO = VALUES(CDPROJETO),
        CDOBJETO = VALUES(CDOBJETO),
        USUARIO = VALUES(USUARIO),
        DTINCLUSAO = VALUES(DTINCLUSAO),
        updated_at = NOW()
";

try {
    $stmt = $pdo->prepare($sql);
    $params = [
        $nupatrimonio, $depatrimonio, $situacao, $marca, $modelo, $cor,
        $cdlocal, $cdfunc, $cdprojeto, $cdobjeto, $usuario, $dtaquisicao
    ];
    
    echo "\nParâmetros do execute:\n";
    for ($i = 0; $i < count($params); $i++) {
        echo "  [$i] = '" . $params[$i] . "'\n";
    }
    echo "\n";
    
    $stmt->execute($params);
    
    $rowCount = $stmt->rowCount();
    echo "✅ Executado com sucesso!\n";
    echo "   rowCount: $rowCount\n";
    
    if ($rowCount == 1) {
        echo "   Resultado: NOVO registro inserido\n";
    } elseif ($rowCount == 2) {
        echo "   Resultado: Registro ATUALIZADO\n";
    } else {
        echo "   Resultado: Nenhuma alteração (rowCount = $rowCount)\n";
    }
    
    // Verificar no banco
    $check = $pdo->prepare("SELECT NUPATRIMONIO, SITUACAO, USUARIO FROM patr WHERE NUPATRIMONIO = ?");
    $check->execute([$nupatrimonio]);
    $result = $check->fetch(PDO::FETCH_ASSOC);
    
    echo "\nVerificação no banco:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "❌ ERRO no INSERT:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
}

echo "\n✅ Teste concluído!\n";
