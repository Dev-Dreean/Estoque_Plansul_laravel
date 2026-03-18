<?php
$disabled = ini_get('disable_functions');
echo "PHP: ".PHP_BINARY.PHP_EOL;
echo "exec: ".(strpos($disabled,'exec')!==false ? 'BLOQUEADO' : 'OK').PHP_EOL;
echo "shell_exec: ".(strpos($disabled,'shell_exec')!==false ? 'BLOQUEADO' : 'OK').PHP_EOL;
echo "proc_open: ".(strpos($disabled,'proc_open')!==false ? 'BLOQUEADO' : 'OK').PHP_EOL;
echo "nohup: ".(file_exists('/usr/bin/nohup') ? 'disponivel' : 'ausente').PHP_EOL;
echo "php82: ".(file_exists('/usr/local/php/8.2/bin/php') ? 'disponivel' : 'ausente').PHP_EOL;
