<?php
/**
 * GERAR patrimonio.TXT ATUALIZADO a partir do BANCO KINGHOST
 */

$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

echo "Gerando patrimonio.TXT atualizado...\n";

// Header
$header = "NUPATRIMONIO\tSITUACAO\tMARCA\tCDLOCAL\tMODELO\tCOR\tDTAQUISICAO\tDEHISTORICO\tCDMATRFUNCIONARIO\tCDPROJETO\tNUDOCFISCAL\tUSUARIO\tDTOPERACAO\tNUMOF\tCODOBJETO\tFLCONFERIDO\n";
$line = str_repeat("=", 140) . "\n";

$content = $header . $line;

// Buscar patrimônios
$patrimonios = $kinghost->query('
    SELECT 
        NUPATRIMONIO, 
        SITUACAO, 
        MARCA, 
        CDLOCAL, 
        MODELO, 
        COR, 
        DTAQUISICAO, 
        DEPATRIMONIO as DEHISTORICO, 
        CDMATRFUNCIONARIO, 
        CDPROJETO, 
        NUDOCFISCAL, 
        USUARIO, 
        DTOPERACAO, 
        NUMOF, 
        CODOBJETO, 
        FLCONFERIDO
    FROM patr 
    ORDER BY CAST(NUPATRIMONIO AS UNSIGNED)
')->fetchAll(PDO::FETCH_ASSOC);

echo "Processando " . count($patrimonios) . " patrimônios...\n";

foreach ($patrimonios as $p) {
    $row = sprintf(
        "%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n",
        $p['NUPATRIMONIO'],
        $p['SITUACAO'] ?? '<null>',
        $p['MARCA'] ?? '<null>',
        $p['CDLOCAL'] ?? '<null>',
        $p['MODELO'] ?? '<null>',
        $p['COR'] ?? '<null>',
        $p['DTAQUISICAO'] ?? '<null>',
        $p['DEHISTORICO'] ?? '<null>',
        $p['CDMATRFUNCIONARIO'] ?? '<null>',
        $p['CDPROJETO'] ?? '<null>',
        $p['NUDOCFISCAL'] ?? '<null>',
        $p['USUARIO'] ?? '<null>',
        $p['DTOPERACAO'] ?? '<null>',
        $p['NUMOF'] ?? '<null>',
        $p['CODOBJETO'] ?? '<null>',
        $p['FLCONFERIDO'] ?? '<null>'
    );
    
    $content .= $row;
}

// Salvar arquivo
$output_file = 'c:\\Users\\marketing\\Desktop\\MATRIZ - TRABALHOS\\Projeto - Matriz\\plansul\\patrimonio_NOVO.TXT';
file_put_contents($output_file, $content);

echo "✅ Arquivo gerado: $output_file\n";
echo "Total de linhas: " . count($patrimonios) . "\n";

// Backup do antigo
$old_file = 'c:\\Users\\marketing\\Desktop\\MATRIZ - TRABALHOS\\Projeto - Matriz\\plansul\\patrimonio.TXT';
$backup_file = 'c:\\Users\\marketing\\Desktop\\MATRIZ - TRABALHOS\\Projeto - Matriz\\plansul\\patrimonio_BACKUP_' . date('Y-m-d_H-i-s') . '.TXT';

if (file_exists($old_file)) {
    copy($old_file, $backup_file);
    echo "✅ Backup do antigo: $backup_file\n";
    
    // Substituir
    rename($output_file, $old_file);
    echo "✅ patrimonio.TXT atualizado!\n";
}
