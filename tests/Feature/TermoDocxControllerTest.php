<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patrimonio;
use App\Models\Funcionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermoDocxControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Funcionario $funcionario;
    protected Patrimonio $patrimonio;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar funcionário
        $this->funcionario = Funcionario::factory()->create([
            'CDMATRFUNCIONARIO' => '12345',
            'NMFUNCIONARIO' => 'João da Silva'
        ]);

        // Criar usuário autenticado
        $this->user = User::factory()->create([
            'PERFIL' => 'SUP',
            'CDMATRFUNCIONARIO' => $this->funcionario->CDMATRFUNCIONARIO
        ]);

        // Criar patrimônio vinculado ao funcionário
        $this->patrimonio = Patrimonio::factory()->create([
            'CDMATRFUNCIONARIO' => $this->funcionario->CDMATRFUNCIONARIO,
            'DEPATRIMONIO' => 'NOTEBOOK DELL',
            'MODELO' => 'INSPIRON 15',
            'NUSERIE' => 'SN123456789'
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_download_termo()
    {
        $response = $this->get(route('termos.docx.download', $this->patrimonio->NUSEQPATR));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_downloads_termo_for_single_patrimonio()
    {
        // Criar template mock (se não existir)
        $this->createMockTemplate();

        $response = $this->actingAs($this->user)
            ->get(route('termos.docx.download', $this->patrimonio->NUSEQPATR));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->assertHeader('Content-Disposition');

        // Verificar se o nome do arquivo contém a matrícula
        $this->assertStringContainsString(
            $this->funcionario->CDMATRFUNCIONARIO,
            $response->headers->get('Content-Disposition')
        );
    }

    /** @test */
    public function it_downloads_termo_for_multiple_patrimonios()
    {
        $this->createMockTemplate();

        // Criar mais patrimônios para o mesmo funcionário
        $patrimonio2 = Patrimonio::factory()->create([
            'CDMATRFUNCIONARIO' => $this->funcionario->CDMATRFUNCIONARIO,
            'DEPATRIMONIO' => 'MOUSE USB'
        ]);

        $patrimonio3 = Patrimonio::factory()->create([
            'CDMATRFUNCIONARIO' => $this->funcionario->CDMATRFUNCIONARIO,
            'DEPATRIMONIO' => 'TECLADO'
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('termos.docx.batch'), [
                'ids' => [
                    $this->patrimonio->NUSEQPATR,
                    $patrimonio2->NUSEQPATR,
                    $patrimonio3->NUSEQPATR
                ]
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }

    /** @test */
    public function it_validates_required_ids_for_batch_download()
    {
        $response = $this->actingAs($this->user)
            ->post(route('termos.docx.batch'), [
                'ids' => []
            ]);

        $response->assertSessionHasErrors('ids');
    }

    /** @test */
    public function it_prevents_mixing_different_funcionarios()
    {
        $this->createMockTemplate();

        // Criar outro funcionário e patrimônio
        $outroFuncionario = Funcionario::factory()->create([
            'CDMATRFUNCIONARIO' => '99999'
        ]);

        $outroPatrimonio = Patrimonio::factory()->create([
            'CDMATRFUNCIONARIO' => $outroFuncionario->CDMATRFUNCIONARIO
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('termos.docx.batch'), [
                'ids' => [
                    $this->patrimonio->NUSEQPATR,
                    $outroPatrimonio->NUSEQPATR
                ]
            ]);

        $response->assertStatus(422); // Unprocessable Entity
    }

    /** @test */
    public function it_enforces_authorization_policy()
    {
        // Criar outro usuário sem permissão
        /** @var User $outroUser */
        $outroUser = User::factory()->create([
            'PERFIL' => 'USR',
            'CDMATRFUNCIONARIO' => '88888'
        ]);

        $response = $this->actingAs($outroUser)
            ->get(route('termos.docx.download', $this->patrimonio->NUSEQPATR));

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function it_returns_404_for_non_existent_patrimonio()
    {
        $response = $this->actingAs($this->user)
            ->get(route('termos.docx.download', 999999));

        $response->assertStatus(404);
    }

    /** @test */
    public function it_respects_max_items_limit()
    {
        $this->createMockTemplate();

        // Criar 201 patrimônios (acima do limite de 200)
        $ids = [];
        for ($i = 0; $i < 201; $i++) {
            $pat = Patrimonio::factory()->create([
                'CDMATRFUNCIONARIO' => $this->funcionario->CDMATRFUNCIONARIO
            ]);
            $ids[] = $pat->NUSEQPATR;
        }

        $response = $this->actingAs($this->user)
            ->post(route('termos.docx.batch'), [
                'ids' => $ids
            ]);

        $response->assertSessionHasErrors('ids');
    }

    /** @test */
    public function it_handles_patrimonios_without_funcionario()
    {
        $this->createMockTemplate();

        // Patrimônio sem funcionário atribuído
        $patrimonioSemFunc = Patrimonio::factory()->create([
            'CDMATRFUNCIONARIO' => null
        ]);

        // Super Admin deve conseguir acessar
        $response = $this->actingAs($this->user)
            ->get(route('termos.docx.download', $patrimonioSemFunc->NUSEQPATR));

        $response->assertStatus(200);
    }

    /**
     * Helper para criar template mock
     */
    protected function createMockTemplate(): void
    {
        $templateDir = storage_path('app/templates');

        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }

        $templatePath = $templateDir . '/termo_itens.docx';

        // Se o template já existe, não recriar
        if (file_exists($templatePath)) {
            return;
        }

        // Criar um template DOCX básico para testes
        // Nota: Para testes reais, você deve ter um template válido
        // Por ora, criamos um arquivo vazio que passará nas verificações básicas

        // Copiar de um template de exemplo se existir
        $exampleTemplate = base_path('tests/fixtures/termo_template_example.docx');

        if (file_exists($exampleTemplate)) {
            copy($exampleTemplate, $templatePath);
        } else {
            // Criar arquivo vazio como fallback (para CI/CD)
            touch($templatePath);
        }
    }
}
