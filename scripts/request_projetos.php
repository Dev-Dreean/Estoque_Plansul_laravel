<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/projetos', 'GET');
$response = $kernel->handle($request);
file_put_contents(__DIR__ . '/../debug_projetos_full.html', $response->getContent());
echo 'Saved to debug_projetos_full.html\n';
$kernel->terminate($request, $response);
