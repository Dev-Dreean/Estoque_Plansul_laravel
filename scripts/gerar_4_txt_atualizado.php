<?php
/**
 * GERAR 4 ARQUIVOS TXT ATUALIZADOS a partir dos bancos sincronizados
 */

$local = new PDO('mysql:host=127.0.0.1;dbname=cadastros_plansul;charset=utf8mb4', 'root', '');
$kinghost = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04;charset=utf8mb4', 'plansul004_add2', 'A33673170a');

$base_path = 'c:\\Users\\marketing\\Desktop\\MATRIZ - TRABALHOS\\Projeto - Matriz\\plansul\\storage\\imports\\Novo import\\';

echo "Gerando 4 arquivos TXT atualizados...\n\n";

// =========================================================================
// 1. PROJETOS_TABFANTASIA.TXT
// =========================================================================

echo "1️⃣  Gerando Projetos_tabfantasia.txt...\n";

$header = "CDPROJETO\tNOMEPROJETO\tLOCAL\tUF\n";
$line = str_repeat("=", 60) . "\n";

$content = $header . $line;

$projetos = $local->query('SELECT CDPROJETO, NOMEPROJETO, LOCAL, UF FROM tabfant ORDER BY CAST(CDPROJETO AS UNSIGNED)')->fetchAll(PDO::FETCH_ASSOC);

foreach ($projetos as $p) {
    $content .= sprintf("%s\t%s\t%s\t%s\n",
        $p['CDPROJETO'],
        $p['NOMEPROJETO'] ?? '<null>',
        $p['LOCAL'] ?? '<null>',
        $p['UF'] ?? '<null>'
    );
}

file_put_contents($base_path . 'Projetos_tabfantasia.txt', $content);
echo "   ✅ " . count($projetos) . " projetos salvos\n";

// =========================================================================
// 2. LOCALPROJETO.TXT
// =========================================================================

echo "\n2️⃣  Gerando LocalProjeto.TXT...\n";

$header = "CDLOCAL\tDELOCAL\tCODIGO_PROJETO\tFLATIVO\n";
$line = str_repeat("=", 120) . "\n";

$content = $header . $line;

$locais = $local->query('SELECT cdlocal, delocal, codigo_projeto, flativo FROM locais_projeto ORDER BY CAST(cdlocal AS UNSIGNED)')->fetchAll(PDO::FETCH_ASSOC);

foreach ($locais as $l) {
    $content .= sprintf("%s\t%s\t%s\t%s\n",
        $l['cdlocal'],
        $l['delocal'] ?? '<null>',
        $l['codigo_projeto'] ?? '<null>',
        $l['flativo'] ?? '<null>'
    );
}

file_put_contents($base_path . 'LocalProjeto.TXT', $content);
echo "   ✅ " . count($locais) . " locais salvos\n";

// =========================================================================
// 3. PATRIMONIO.TXT
// =========================================================================

echo "\n3️⃣  Gerando Patrimonio.txt...\n";

$header = "NUPATRIMONIO\tSITUACAO\tMARCA\tCDLOCAL\tMODELO\tCOR\tDTAQUISICAO\tDEHISTORICO\tCDMATRFUNCIONARIO\tCDPROJETO\tUSUARIO\tDTOPERACAO\tNUMOF\tCODOBJETO\tFLCONFERIDO\n";
$line = str_repeat("=", 180) . "\n";

$content = $header . $line;

$patrimonios = $local->query('SELECT NUPATRIMONIO, SITUACAO, MARCA, CDLOCAL, MODELO, COR, DTAQUISICAO, DEHISTORICO, CDMATRFUNCIONARIO, CDPROJETO, USUARIO, DTOPERACAO, NUMOF, CODOBJETO, FLCONFERIDO FROM patr ORDER BY CAST(NUPATRIMONIO AS UNSIGNED)')->fetchAll(PDO::FETCH_ASSOC);

foreach ($patrimonios as $p) {
    $content .= sprintf("%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n",
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
        $p['USUARIO'] ?? '<null>',
        $p['DTOPERACAO'] ?? '<null>',
        $p['NUMOF'] ?? '<null>',
        $p['CODOBJETO'] ?? '<null>',
        $p['FLCONFERIDO'] ?? '<null>'
    );
}

file_put_contents($base_path . 'Patrimonio.txt', $content);
echo "   ✅ " . count($patrimonios) . " patrimônios salvos\n";

// =========================================================================
// 4. HIST_MOVPATR.TXT
// =========================================================================

echo "\n4️⃣  Gerando Hist_movpatr.TXT...\n";

$header = "NUPATR\tCODPROJ\tDTMOVI\tTIPO\tUSUARIO\tDTOPERACAO\n";
$line = str_repeat("=", 90) . "\n";

$content = $header . $line;

$historicos = $local->query('SELECT NUPATR, CODPROJ, DTOPERACAO as DTMOVI, TIPO, USUARIO, DTOPERACAO FROM movpartr ORDER BY CAST(NUPATR AS UNSIGNED)')->fetchAll(PDO::FETCH_ASSOC);

foreach ($historicos as $h) {
    $content .= sprintf("%s\t%s\t%s\t%s\t%s\t%s\n",
        $h['NUPATR'],
        $h['CODPROJ'] ?? '<null>',
        $h['DTMOVI'] ?? '<null>',
        $h['TIPO'] ?? '<null>',
        $h['USUARIO'] ?? '<null>',
        $h['DTOPERACAO'] ?? '<null>'
    );
}

file_put_contents($base_path . 'Hist_movpatr.TXT', $content);
echo "   ✅ " . count($historicos) . " históricos salvos\n";

echo "\n✅ Todos os 4 arquivos TXT foram atualizados com sucesso!\n";
