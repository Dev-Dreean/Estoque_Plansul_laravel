<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul', 'root', '');

echo "═══════════════════════════════════════════════════════════════\n";
echo "ANÁLISE DETALHADA - PROBLEMAS ENCONTRADOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Patrimônio 19269
echo "❌ PATRIMÔNIO 19269\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "PROBLEMA: O usuário relata que deveria estar no Projeto 200 (Filial RS)\n";
echo "STATUS ATUAL: Está no Projeto 8 (SEDE) com CDLOCAL=3\n\n";

$stmt = $pdo->prepare("SELECT * FROM tabfant WHERE CDPROJETO = 200");
$stmt->execute();
$proj200 = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Informações do Projeto 200:\n";
echo "  • Nome: " . ($proj200 ? $proj200['NOMEPROJETO'] : "N/A") . "\n";
echo "  • ID: " . ($proj200 ? $proj200['id'] : "N/A") . "\n";
echo "  • UF: " . ($proj200 ? $proj200['UF'] : "N/A") . "\n";

// Locais associados ao Projeto 200
if ($proj200) {
    $stmt = $pdo->prepare("SELECT * FROM locais_projeto WHERE tabfant_id = ? ORDER BY delocal");
    $stmt->execute([$proj200['id']]);
    $locaisProjeto200 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n  Locais disponíveis para Projeto 200:\n";
    foreach ($locaisProjeto200 as $local) {
        echo "    • ID={$local['id']}, CDLOCAL={$local['cdlocal']}: {$local['delocal']}\n";
    }
}

echo "\n\n";

// 2. Patrimônio 22414
echo "✅ PATRIMÔNIO 22414\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "STATUS: Já está correto no Projeto 8 (SEDE)\n";
echo "CDLOCAL: 3 (SETOR VEICULO)\n\n";

// 3. Projeto 736 - CEF-MG-2
echo "⚠️  PROJETO 736 - CEF-MG-2 (400 patrimônios)\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "PROBLEMA: 400 patrimônios cadastrados em um local único (CDLOCAL=501 'ABC')\n";
echo "Causa provável: Erro de mapeamento durante importação\n\n";

// Verificar locais disponíveis para Projeto 736
$stmt = $pdo->prepare("SELECT * FROM locais_projeto WHERE tabfant_id = 736");
$stmt->execute();
$locaisCEF = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Locais disponíveis para CEF-MG-2:\n";
foreach ($locaisCEF as $local) {
    echo "  • ID={$local['id']}, CDLOCAL={$local['cdlocal']}: {$local['delocal']}\n";
}

// Contar patrimônios por local no projeto 736
echo "\nDistribuição de patrimônios no Projeto 736:\n";
$stmt = $pdo->prepare("
    SELECT p.CDLOCAL, lp.delocal, COUNT(*) as total
    FROM patr p
    LEFT JOIN locais_projeto lp ON lp.id = p.CDLOCAL
    WHERE p.CDPROJETO = 736
    GROUP BY p.CDLOCAL, lp.delocal
    ORDER BY total DESC
");
$stmt->execute();
$distribuicao = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($distribuicao as $dist) {
    echo "  • CDLOCAL {$dist['CDLOCAL']} ({$dist['delocal']}): {$dist['total']} patrimônios\n";
}

echo "\n\n";

// 4. Análise global de inconsistências
echo "🔍 RESUMO DE INCONSISTÊNCIAS ENCONTRADAS\n";
echo "─────────────────────────────────────────────────────────────\n";

// Inconsistências restantes
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM patr p
    WHERE p.CDPROJETO NOT IN (
        SELECT DISTINCT tabfant_id FROM locais_projeto WHERE id = p.CDLOCAL
    ) OR p.CDPROJETO IS NULL
");
$stmt->execute();
$inconsistent = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total de patrimônios com inconsistências restantes: " . ($inconsistent['total'] ?? 0) . "\n";

// Contar por projeto
$stmt = $pdo->prepare("
    SELECT t.CDPROJETO, t.NOMEPROJETO, COUNT(p.NUPATRIMONIO) as total_inconsistente
    FROM patr p
    INNER JOIN tabfant t ON t.id = p.CDPROJETO
    WHERE p.CDPROJETO NOT IN (
        SELECT DISTINCT tabfant_id FROM locais_projeto WHERE id = p.CDLOCAL
    )
    GROUP BY t.CDPROJETO, t.NOMEPROJETO
    ORDER BY total_inconsistente DESC
    LIMIT 10
");
$stmt->execute();
$projetosInconsistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($projetosInconsistentes)) {
    echo "\nProjetos com mais inconsistências:\n";
    foreach ($projetosInconsistentes as $p) {
        echo "  • Projeto {$p['CDPROJETO']} ({$p['NOMEPROJETO']}): {$p['total_inconsistente']} patrimônios\n";
    }
}

echo "\n";
