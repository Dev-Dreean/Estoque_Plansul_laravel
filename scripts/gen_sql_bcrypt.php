<?php
// scripts/gen_sql_bcrypt.php
$input = __DIR__ . '/../usuarios_insert.sql';
$output = __DIR__ . '/../usuarios_insert_bcrypt.sql';
$lines = file($input, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$out = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '--') === 0) continue;
    // Match VALUES ('LOGIN', 'NAME', 'PERFIL', SHA2('temporaria123', 256), 1);
    if (preg_match("/INSERT INTO usuario \(NMLOGIN, NOMEUSER, PERFIL, SENHA, LGATIVO\) VALUES \('(.+?)', '(.+?)', '(.+?)', SHA2\('(.+?)', 256\), (\d)\);/", $line, $m)) {
        [$all, $login, $name, $perfil, $plain, $ativo] = $m;
        // generate bcrypt hash
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        // set must_change_password true (1) and LGATIVO as original
        $sql = "INSERT INTO usuario (NMLOGIN, NOMEUSER, PERFIL, SENHA, LGATIVO, must_change_password) VALUES ('" . addslashes($login) . "', '" . addslashes($name) . "', '" . addslashes($perfil) . "', '" . addslashes($hash) . "', " . intval($ativo) . ", 1);";
        $out[] = $sql;
    }
}
file_put_contents($output, implode("\n", $out) . "\n");
echo "Wrote $output\n";
