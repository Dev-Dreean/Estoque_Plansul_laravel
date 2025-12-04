<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul', 'root', '');

echo "═══════════════════════════════════════════════════════════════\n";
echo "CORREÇÃO DO PATRIMÔNIO 19269\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar dados atuais
echo "1. DADOS ATUAIS DO PATRIMÔNIO 19269:\n";
$stmt = $pdo->prepare("
    SELECT p.NUPATRIMONIO, p.CDLOCAL, p.CDPROJETO, 
           lp.delocal, t.NOMEPROJETO, t.CDPROJETO as PROJ_CODE
    FROM patr p
    LEFT JOIN locais_projeto lp ON lp.id = p.CDLOCAL
    LEFT JOIN tabfant t ON t.id = p.CDPROJETO
    WHERE p.NUPATRIMONIO = 19269
");
$stmt->execute();
$atual = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Local atual: ID={$atual['CDLOCAL']} ({$atual['delocal']})\n";
echo "   Projeto atual: ID={$atual['CDPROJETO']} ({$atual['NOMEPROJETO']}, CDPROJETO={$atual['PROJ_CODE']})\n\n";

// 2. Listar locais do Projeto 200
echo "2. LOCAIS DISPONÍVEIS DO PROJETO 200 (FILIAL-RS):\n";
$stmt = $pdo->prepare("
    SELECT id, cdlocal, delocal 
    FROM locais_projeto 
    WHERE tabfant_id = 200
    ORDER BY delocal
");
$stmt->execute();
$locais = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($locais as $index => $local) {
    echo "   [{$index}] ID={$local['id']} | CDLOCAL={$local['cdlocal']} | {$local['delocal']}\n";
}

echo "\n3. OPÇÃO RECOMENDADA:\n";
echo "   Usando o local FILIAL (ID=1422, CDLOCAL=1431) - local mais geral para a filial\n\n";

// 3. Fazer a correção
echo "4. EXECUTANDO CORREÇÃO...\n";
$novoLocalId = 1422;  // FILIAL
$novoProjetoId = 200; // FILIAL-RS

$stmt = $pdo->prepare("
    UPDATE patr 
    SET CDLOCAL = ?, CDPROJETO = ?
    WHERE NUPATRIMONIO = 19269
");

try {
    $result = $stmt->execute([$novoLocalId, $novoProjetoId]);
    
    if ($result) {
        echo "   ✅ Patrimônio 19269 atualizado com sucesso!\n\n";
        
        // 5. Verificar resultado
        echo "5. NOVO ESTADO DO PATRIMÔNIO 19269:\n";
        $stmt = $pdo->prepare("
            SELECT p.NUPATRIMONIO, p.CDLOCAL, p.CDPROJETO, 
                   lp.delocal, t.NOMEPROJETO, t.CDPROJETO as PROJ_CODE
            FROM patr p
            LEFT JOIN locais_projeto lp ON lp.id = p.CDLOCAL
            LEFT JOIN tabfant t ON t.id = p.CDPROJETO
            WHERE p.NUPATRIMONIO = 19269
        ");
        $stmt->execute();
        $novo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✅ Local: ID={$novo['CDLOCAL']} ({$novo['delocal']})\n";
        echo "   ✅ Projeto: ID={$novo['CDPROJETO']} ({$novo['NOMEPROJETO']}, CDPROJETO={$novo['PROJ_CODE']})\n";
    } else {
        echo "   ❌ Erro ao atualizar patrimônio\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n";
