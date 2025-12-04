<?php
// Configuração do banco de dados
$host = '127.0.0.1';
$database = 'cadastros_plansul';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICAÇÃO DE PATRIMÔNIOS ESPECÍFICOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar patrimônio 19269 (deve estar no projeto 200 - Filial RS)
echo "1️⃣  PATRIMÔNIO 19269 (Esperado: Projeto 200 - Filial RS)\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $pdo->prepare("
    SELECT p.NUPATRIMONIO, p.CDLOCAL, p.CDPROJETO, 
           lp.delocal, lp.id as LOCAL_ID, t.NOMEPROJETO, t.CDPROJETO as PROJ_CODE
    FROM patr p
    LEFT JOIN locais_projeto lp ON lp.id = p.CDLOCAL
    LEFT JOIN tabfant t ON t.id = p.CDPROJETO
    WHERE p.NUPATRIMONIO = 19269
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "Status: " . ($result['CDPROJETO'] == 200 ? "✅ CORRETO" : "❌ INCORRETO") . "\n";
    echo "CDLOCAL: {$result['CDLOCAL']}\n";
    echo "CDPROJETO: {$result['CDPROJETO']}\n";
    echo "Local: " . ($result['delocal'] ?? "N/A") . " (ID: {$result['LOCAL_ID']})\n";
    echo "Projeto: " . ($result['NOMEPROJETO'] ?? "N/A") . " (CDPROJETO: {$result['PROJ_CODE']})\n";
} else {
    echo "❌ Patrimônio 19269 não encontrado\n";
}

echo "\n\n";

// 2. Verificar patrimônio 22414 (deve estar no projeto 8)
echo "2️⃣  PATRIMÔNIO 22414 (Esperado: Projeto 8 - Ararangua)\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $pdo->prepare("
    SELECT p.NUPATRIMONIO, p.CDLOCAL, p.CDPROJETO, 
           lp.delocal, lp.id as LOCAL_ID, t.NOMEPROJETO, t.CDPROJETO as PROJ_CODE
    FROM patr p
    LEFT JOIN locais_projeto lp ON lp.id = p.CDLOCAL
    LEFT JOIN tabfant t ON t.id = p.CDPROJETO
    WHERE p.NUPATRIMONIO = 22414
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "Status: " . ($result['CDPROJETO'] == 8 ? "✅ CORRETO" : "❌ INCORRETO") . "\n";
    echo "CDLOCAL: {$result['CDLOCAL']}\n";
    echo "CDPROJETO: {$result['CDPROJETO']}\n";
    echo "Local: " . ($result['delocal'] ?? "N/A") . " (ID: {$result['LOCAL_ID']})\n";
    echo "Projeto: " . ($result['NOMEPROJETO'] ?? "N/A") . " (CDPROJETO: {$result['PROJ_CODE']})\n";
} else {
    echo "❌ Patrimônio 22414 não encontrado\n";
}

echo "\n\n";

// 3. Verificar quantos patrimônios estão no Projeto 736 (CEF-MG-2)
echo "3️⃣  VERIFICAÇÃO DO PROJETO 736 - CEF-MG-2\n";
echo "─────────────────────────────────────────────────────────────\n";

$stmt = $pdo->prepare("
    SELECT id, NOMEPROJETO, CDPROJETO 
    FROM tabfant 
    WHERE CDPROJETO = 736
");
$stmt->execute();
$projeto = $stmt->fetch(PDO::FETCH_ASSOC);

if ($projeto) {
    echo "Projeto: {$projeto['NOMEPROJETO']}\n";
    echo "ID: {$projeto['id']}\n";
    echo "CDPROJETO: {$projeto['CDPROJETO']}\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patr WHERE CDPROJETO = 736");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    $patrimoniosCEF = $count['total'] ?? 0;
    
    echo "Total de patrimônios: $patrimoniosCEF\n";
    
    if ($patrimoniosCEF > 0) {
        echo "\nPrimeiros 30 patrimônios cadastrados no Projeto 736:\n";
        $stmt = $pdo->prepare("
            SELECT p.NUPATRIMONIO, p.CDLOCAL, 
                   lp.delocal, lp.id as LOCAL_ID
            FROM patr p
            LEFT JOIN locais_projeto lp ON lp.id = p.CDLOCAL
            WHERE p.CDPROJETO = 736
            ORDER BY p.NUPATRIMONIO
            LIMIT 30
        ");
        $stmt->execute();
        $patrimonios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($patrimonios as $p) {
            echo sprintf("  • %-8d | Local: %-35s | CDLOCAL: %d\n", 
                $p['NUPATRIMONIO'],
                ($p['delocal'] ? substr($p['delocal'], 0, 33) : "N/A"),
                $p['CDLOCAL']
            );
        }
        
        if ($patrimoniosCEF > 30) {
            echo "\n  ... e mais " . ($patrimoniosCEF - 30) . " patrimônios\n";
        }
    }
} else {
    echo "❌ Projeto 736 não encontrado\n";
}

echo "\n";
