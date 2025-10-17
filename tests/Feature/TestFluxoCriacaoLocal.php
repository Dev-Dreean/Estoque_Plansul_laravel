<?php

use App\Models\ProjetoFilial;
use App\Models\LocalProjeto;
use App\Models\Tabfant;

/* Group wrapper removed for PHPUnit compatibility: tests are top-level */

test('criar local com projeto e recuperar', function () {
    // 1. Criar um projeto válido
    $projeto = ProjetoFilial::create([
        'CDPROJETO' => 999,
        'NOMEPROJETO' => 'TESTE-COMPLETO',
        'flativo' => 'S',
    ]);

    expect($projeto)->not->toBeNull()
        ->and($projeto->CDPROJETO)->toBe(999);

    // 2. Criar um Tabfant vinculado ao projeto
    $tabfant = Tabfant::create([
        'CDPROJETO' => 999,
        'NOMEPROJETO' => 'TESTE-COMPLETO',
        'LOCAL' => 'SANTA CATARINA',
    ]);

    expect($tabfant)->not->toBeNull()
        ->and($tabfant->CDPROJETO)->toBe(999);

    // 3. Criar um novo local vinculado ao tabfant via API
    $response = $this->post('/api/locais/criar', [
        'cdlocal' => 999,
        'local' => 'SANTA CATARINA',
        'cdprojeto' => 999,
    ]);

    $response->assertStatus(200);
    $data = $response->json();
    $tabfant_id = $data['tabfant_id'] ?? null;

    expect($tabfant_id)->not->toBeNull();

    // 4. Buscar o local criado
    $searchResponse = $this->get('/api/locais/buscar?termo=999');
    $searchResponse->assertStatus(200);
    $locais = $searchResponse->json();

    expect($locais)->not->toBeEmpty();

    // 5. Verificar que o projeto foi recuperado
    $localComProjeto = collect($locais)->first(function ($l) use ($tabfant_id) {
        return $l['tabfant_id'] == $tabfant_id;
        /* End of previously grouped tests */

        expect($localComProjeto)->not->toBeNull()
            ->and($localComProjeto['CDPROJETO'])->toBe(999)
            ->and($localComProjeto['NOMEPROJETO'])->toBe('TESTE-COMPLETO');
    });

    test('buscar projetos sem filtro retorna todos', function () {
        // Criar alguns projetos
        ProjetoFilial::create(['CDPROJETO' => 111, 'NOMEPROJETO' => 'PROJ-A', 'flativo' => 'S']);
        ProjetoFilial::create(['CDPROJETO' => 222, 'NOMEPROJETO' => 'PROJ-B', 'flativo' => 'S']);
        ProjetoFilial::create(['CDPROJETO' => 333, 'NOMEPROJETO' => 'PROJ-C', 'flativo' => 'S']);

        // Buscar sem filtro (q vazio)
        $response = $this->get('/api/projetos/pesquisar?q=');
        $response->assertStatus(200);
        $projetos = $response->json();

        expect($projetos)->toHaveCount(3);
    });

    test('buscar locais com múltiplos resultados', function () {
        // Criar projeto
        ProjetoFilial::create([
            'CDPROJETO' => 777,
            'NOMEPROJETO' => 'MULTI-TEST',
            'flativo' => 'S',
        ]);

        // Criar dois tabfants para o mesmo código
        $tabfant1 = Tabfant::create([
            'CDPROJETO' => 777,
            'NOMEPROJETO' => 'MULTI-TEST',
            'LOCAL' => 'CURITIBA',
        ]);

        $tabfant2 = Tabfant::create([
            'CDPROJETO' => 777,
            'NOMEPROJETO' => 'MULTI-TEST',
            'LOCAL' => 'SAO PAULO',
        ]);

        // Criar dois locais
        LocalProjeto::create([
            'cdlocal' => 777,
            'delocal' => 'CURITIBA',
            'tabfant_id' => $tabfant1->id,
            'flativo' => 'S',
        ]);

        LocalProjeto::create([
            'cdlocal' => 777,
            'delocal' => 'SAO PAULO',
            'tabfant_id' => $tabfant2->id,
            'flativo' => 'S',
        ]);

        // Buscar
        $response = $this->get('/api/locais/buscar?termo=777');
        $response->assertStatus(200);
        $locais = $response->json();

        expect($locais)->toHaveCount(2);

        // Verificar que ambos têm projeto
        foreach ($locais as $local) {
            expect($local['CDPROJETO'])->not->toBeNull()
                ->and($local['tabfant_id'])->not->toBeNull();
        }
    });
});
