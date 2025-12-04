<?php
/**
 * COMPARAR patrimonio.TXT COM BANCO DE DADOS KINGHOST
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   COMPARAÇÃO: patrimonio.TXT vs BANCO KINGHOST                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Ler o TXT
$txt_file = 'c:\\Users\\marketing\\Desktop\\MATRIZ - TRABALHOS\\Projeto - Matriz\\plansul\\patrimonio.TXT';

if (!file_exists($txt_file)) {
    echo "❌ Arquivo não encontrado: $txt_file\n";
    exit(1);
}

echo "Lendo patrimonio.TXT...\n";
$lines = file($txt_file);

// Contar linhas (ignorar header)
$data_lines = count($lines) - 2; // Menos header e linha de ====
echo "Linhas de dados no TXT: $data_lines\n";

// Contar no banco
$db_count = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
echo "Registros no BANCO: $db_count\n\n";

if ($data_lines == $db_count) {
    echo "✅ Contagem bate! ($data_lines = $db_count)\n\n";
} else {
    echo "⚠️  DIFERENÇA NA CONTAGEM: $data_lines (TXT) vs $db_count (BANCO)\n\n";
}

// Validar amostra
echo "Validando amostra de patrimônios...\n";
echo str_repeat("─", 70) . "\n\n";

$samples = [5640, 5679, 5746, 456, 1, 3, 7, 9, 38, 45];
$errors = 0;
$matches = 0;

foreach ($samples as $id) {
    // Buscar no TXT
    $txt_data = null;
    foreach ($lines as $line) {
        if (preg_match('/^' . $id . '\s+/', $line)) {
            $txt_data = $line;
            break;
        }
    }
    
    // Buscar no banco
    $db_data = $kinghost->query("SELECT NUPATRIMONIO, USUARIO, CDPROJETO, CDMATRFUNCIONARIO FROM patr WHERE NUPATRIMONIO = $id")->fetch();
    
    if (!$db_data && !$txt_data) {
        echo "⚠️  Patrimônio $id: não existe em nenhum lugar\n";
        continue;
    }
    
    if (!$txt_data) {
        echo "❌ Patrimônio $id: falta no TXT (existe no BANCO)\n";
        $errors++;
        continue;
    }
    
    if (!$db_data) {
        echo "❌ Patrimônio $id: falta no BANCO (existe no TXT)\n";
        $errors++;
        continue;
    }
    
    // Extrair dados do TXT
    $fields = preg_split('/\s{2,}/', trim($txt_data));
    $txt_id = (int)$fields[0];
    $txt_user = isset($fields[12]) && $fields[12] != '<null>' && $fields[12] != '' ? trim($fields[12]) : null;
    $txt_cdproj = isset($fields[11]) && $fields[11] != '<null>' && $fields[11] != '' ? (int)trim($fields[11]) : null;
    $txt_matfunc = isset($fields[10]) && $fields[10] != '<null>' && $fields[10] != '' ? (int)trim($fields[10]) : null;
    
    // Comparar
    $match = true;
    if ($txt_user != $db_data['USUARIO']) {
        $match = false;
    }
    if ($txt_cdproj != $db_data['CDPROJETO']) {
        $match = false;
    }
    if ($txt_matfunc != $db_data['CDMATRFUNCIONARIO']) {
        $match = false;
    }
    
    if ($match) {
        echo "✅ Patrimônio $id: OK\n";
        $matches++;
    } else {
        echo "⚠️  Patrimônio $id: DIVERGÊNCIA\n";
        echo "   TXT: USER={$txt_user}, PROJ={$txt_cdproj}, MATFUNC={$txt_matfunc}\n";
        echo "   DB:  USER={$db_data['USUARIO']}, PROJ={$db_data['CDPROJETO']}, MATFUNC={$db_data['CDMATRFUNCIONARIO']}\n";
        $errors++;
    }
}

echo "\n" . str_repeat("─", 70) . "\n\n";
echo "RESULTADO: $matches corretos, $errors com divergências\n";

if ($errors == 0 && $data_lines == $db_count) {
    echo "\n✅ TXT E BANCO ESTÃO SINCRONIZADOS PERFEITAMENTE!\n";
} else {
    echo "\n⚠️  Há diferenças entre TXT e BANCO\n";
}
