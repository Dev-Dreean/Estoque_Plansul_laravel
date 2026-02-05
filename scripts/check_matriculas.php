<?php
// Verificar 8 matrículas específicas via PDO

$dsn = 'mysql:host=127.0.0.1;dbname=cadastros_plansul';
$pdo = new PDO($dsn, 'root', '');

$matriculas = [198370, 199158, 199036, 199855, 199856, 199857, 199858, 199859];
$placeholders = implode(',', array_fill(0, count($matriculas), '?'));

$sql = "SELECT CDMATRFUNCIONARIO, NMFUNCIONARIO, DTADMISSAO FROM funcionarios WHERE CDMATRFUNCIONARIO IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($matriculas);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== MATRÍCULAS NO BANCO LOCAL ===\n\n";
$encontrados = 0;
$encontrados_ids = [];

foreach ($result as $row) {
    echo "✅ " . $row['CDMATRFUNCIONARIO'] . " - " . $row['NMFUNCIONARIO'] . " (admissão: " . ($row['DTADMISSAO'] ?? '—') . ")\n";
    $encontrados++;
    $encontrados_ids[] = $row['CDMATRFUNCIONARIO'];
}

echo "\nTotal encontrados: $encontrados de 8\n";

if ($encontrados < 8) {
    echo "\n❌ FALTAM MATRÍCULAS:\n";
    $faltantes = array_diff($matriculas, $encontrados_ids);
    foreach ($faltantes as $mat) {
        echo "   - $mat\n";
    }
} else {
    echo "\n✅ TODAS AS 8 MATRÍCULAS ESTÃO SINCRONIZADAS!\n";
}


