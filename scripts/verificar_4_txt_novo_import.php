<?php
/**
 * COMPARAR 4 ARQUIVOS TXT DO NOVO IMPORT COM BANCO LOCAL E KINGHOST
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

$base_path = 'c:\\Users\\marketing\\Desktop\\MATRIZ - TRABALHOS\\Projeto - Matriz\\plansul\\storage\\imports\\Novo import\\';

echo "╔════════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║   VERIFICAÇÃO: 4 ARQUIVOS TXT vs BANCO LOCAL e KINGHOST                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════════╝\n\n";

// =========================================================================
// 1. PROJETOS_TABFANTASIA.TXT
// =========================================================================

echo "📋 1. Projetos_tabfantasia.txt (TABFANT)\n";
echo str_repeat("─", 90) . "\n";

$txt = file_get_contents($base_path . 'Projetos_tabfantasia.txt');
$txt_lines = array_filter(explode("\n", $txt), function($l) { return trim($l) && !preg_match('/^===/', $l); });
$txt_data_lines = array_values(array_slice($txt_lines, 1)); // skip header

$local_proj = $local->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
$kinghost_proj = $kinghost->query('SELECT COUNT(*) FROM tabfant')->fetch()[0];
$txt_proj_count = count($txt_data_lines);

printf("TXT linhas:    %4d\nLOCAL banco:   %4d\nKINGHOST banco:%4d\n\n", $txt_proj_count, $local_proj, $kinghost_proj);

if ($txt_proj_count == $local_proj && $local_proj == $kinghost_proj) {
    echo "✅ TABFANT: Contagem bate perfeitamente!\n\n";
} else {
    echo "⚠️  TABFANT: Diferenças na contagem!\n\n";
}

// =========================================================================
// 2. LOCALPROJECT.TXT
// =========================================================================

echo "📋 2. LocalProjeto.TXT (LOCAIS_PROJETO)\n";
echo str_repeat("─", 90) . "\n";

$txt = file_get_contents($base_path . 'LocalProjeto.TXT');
$txt_lines = array_filter(explode("\n", $txt), function($l) { return trim($l) && !preg_match('/^===/', $l); });
$txt_data_lines = array_values(array_slice($txt_lines, 1)); // skip header

$local_loc = $local->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];
$kinghost_loc = $kinghost->query('SELECT COUNT(*) FROM locais_projeto')->fetch()[0];
$txt_loc_count = count($txt_data_lines);

printf("TXT linhas:    %4d\nLOCAL banco:   %4d\nKINGHOST banco:%4d\n\n", $txt_loc_count, $local_loc, $kinghost_loc);

if ($txt_loc_count == $local_loc && $local_loc == $kinghost_loc) {
    echo "✅ LOCAIS_PROJETO: Contagem bate perfeitamente!\n\n";
} else {
    echo "⚠️  LOCAIS_PROJETO: Diferenças na contagem!\n\n";
}

// =========================================================================
// 3. PATRIMONIO.TXT
// =========================================================================

echo "📋 3. Patrimonio.txt (PATR)\n";
echo str_repeat("─", 90) . "\n";

$txt = file_get_contents($base_path . 'Patrimonio.txt');
$txt_lines = array_filter(explode("\n", $txt), function($l) { return trim($l) && !preg_match('/^===/', $l); });
$txt_data_lines = array_values(array_slice($txt_lines, 1)); // skip header

$local_patr = $local->query('SELECT COUNT(*) FROM patr')->fetch()[0];
$kinghost_patr = $kinghost->query('SELECT COUNT(*) FROM patr')->fetch()[0];
$txt_patr_count = count($txt_data_lines);

printf("TXT linhas:    %5d\nLOCAL banco:   %5d\nKINGHOST banco:%5d\n\n", $txt_patr_count, $local_patr, $kinghost_patr);

if ($txt_patr_count == $local_patr && $local_patr == $kinghost_patr) {
    echo "✅ PATR: Contagem bate perfeitamente!\n\n";
} else {
    echo "⚠️  PATR: Diferenças na contagem!\n";
    echo "    Diferença TXT vs LOCAL: " . ($txt_patr_count - $local_patr) . "\n";
    echo "    Diferença TXT vs KINGHOST: " . ($txt_patr_count - $kinghost_patr) . "\n\n";
}

// =========================================================================
// 4. HIST_MOVPATR.TXT
// =========================================================================

echo "📋 4. Hist_movpatr.TXT (MOVPARTR)\n";
echo str_repeat("─", 90) . "\n";

$txt = file_get_contents($base_path . 'Hist_movpatr.TXT');
$txt_lines = array_filter(explode("\n", $txt), function($l) { return trim($l) && !preg_match('/^===/', $l); });
$txt_data_lines = array_values(array_slice($txt_lines, 1)); // skip header

$local_mov = $local->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];
$kinghost_mov = $kinghost->query('SELECT COUNT(*) FROM movpartr')->fetch()[0];
$txt_mov_count = count($txt_data_lines);

printf("TXT linhas:    %5d\nLOCAL banco:   %5d\nKINGHOST banco:%5d\n\n", $txt_mov_count, $local_mov, $kinghost_mov);

if ($txt_mov_count == $local_mov && $local_mov == $kinghost_mov) {
    echo "✅ MOVPARTR: Contagem bate perfeitamente!\n\n";
} else {
    echo "⚠️  MOVPARTR: Diferenças na contagem!\n";
    echo "    Diferença TXT vs LOCAL: " . ($txt_mov_count - $local_mov) . "\n";
    echo "    Diferença TXT vs KINGHOST: " . ($txt_mov_count - $kinghost_mov) . "\n\n";
}

// =========================================================================
// RESUMO FINAL
// =========================================================================

echo "\n" . str_repeat("═", 90) . "\n";
echo "RESUMO FINAL\n";
echo str_repeat("═", 90) . "\n\n";

$all_match = 
    ($txt_proj_count == $local_proj && $local_proj == $kinghost_proj) &&
    ($txt_loc_count == $local_loc && $local_loc == $kinghost_loc) &&
    ($txt_patr_count == $local_patr && $local_patr == $kinghost_patr) &&
    ($txt_mov_count == $local_mov && $local_mov == $kinghost_mov);

printf("%-30s | %-10s | %-10s | %-10s\n", "Tabela", "TXT", "LOCAL", "KINGHOST");
printf("%-30s | %-10s | %-10s | %-10s\n", str_repeat("─", 28), str_repeat("─", 8), str_repeat("─", 8), str_repeat("─", 8));
printf("%-30s | %10d | %10d | %10d %s\n", "tabfant (Projetos)", $txt_proj_count, $local_proj, $kinghost_proj, 
    ($txt_proj_count == $local_proj && $local_proj == $kinghost_proj) ? "✅" : "❌");
printf("%-30s | %10d | %10d | %10d %s\n", "locais_projeto (Locais)", $txt_loc_count, $local_loc, $kinghost_loc,
    ($txt_loc_count == $local_loc && $local_loc == $kinghost_loc) ? "✅" : "❌");
printf("%-30s | %10d | %10d | %10d %s\n", "patr (Patrimônios)", $txt_patr_count, $local_patr, $kinghost_patr,
    ($txt_patr_count == $local_patr && $local_patr == $kinghost_patr) ? "✅" : "❌");
printf("%-30s | %10d | %10d | %10d %s\n", "movpartr (Histórico)", $txt_mov_count, $local_mov, $kinghost_mov,
    ($txt_mov_count == $local_mov && $local_mov == $kinghost_mov) ? "✅" : "❌");

echo "\n" . str_repeat("═", 90) . "\n";

if ($all_match) {
    echo "✅ TODOS OS ARQUIVOS TXT ESTÃO SINCRONIZADOS CORRETAMENTE COM OS BANCOS!\n";
} else {
    echo "⚠️  HÁ DIVERGÊNCIAS - Verifique as marcações acima\n";
}

echo str_repeat("═", 90) . "\n";
