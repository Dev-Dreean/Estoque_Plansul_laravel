<?php
chdir('/home/plansul/www/estoque-laravel');
$env = parse_ini_file('.env');
$cmd = 'mysql -h ' . escapeshellarg($env['DB_HOST'])
    . ' -u ' . escapeshellarg($env['DB_USERNAME'])
    . ' -p' . escapeshellarg($env['DB_PASSWORD'])
    . ' -D ' . escapeshellarg($env['DB_DATABASE'])
    . ' -N -e ' . escapeshellarg("SHOW COLUMNS FROM patr LIKE 'NUMMESA'");
passthru($cmd, $code);
exit($code);
