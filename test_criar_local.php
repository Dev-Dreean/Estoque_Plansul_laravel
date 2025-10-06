<?php

use Illuminate\Http\Request;
use App\Http\Controllers\PatrimonioController;

// Simular uma requisição
$request = new Request();
$request->merge([
    'nomeLocal' => 'Teste Local',
    'nomeProjeto' => 'Teste Projeto',
    'cdlocal' => '999',
]);

$controller = new PatrimonioController();
$response = $controller->criarLocalProjeto($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
