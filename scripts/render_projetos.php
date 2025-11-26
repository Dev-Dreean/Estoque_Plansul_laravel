<?php
// Script para renderizar view de projetos para debug
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\ProjetoController;
use Illuminate\Http\Request;

$controller = new ProjetoController();
$request = Request::create('/projetos', 'GET');
$response = $controller->index($request);

if (is_string($response)) {
    echo $response;
} elseif (method_exists($response, 'getContent')) {
    echo $response->getContent();
} else {
    echo 'Tipo de resposta não reconhecido: ' . gettype($response);
}

?>