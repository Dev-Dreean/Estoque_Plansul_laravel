<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Classe base para testes da aplicação.
 * 
 * Estende Illuminate\Foundation\Testing\TestCase fornecendo helpers
 * para testes de features (HTTP) e unitários.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * URL base da aplicação durante testes.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';
}
