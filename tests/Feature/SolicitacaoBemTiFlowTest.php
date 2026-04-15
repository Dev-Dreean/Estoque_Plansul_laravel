<?php

namespace Tests\Feature;

use App\Models\AcessoUsuario;
use App\Models\Funcionario;
use App\Models\LocalProjeto;
use App\Models\SolicitacaoBem;
use App\Models\Tabfant;
use App\Models\User;
use App\Services\SolicitacaoBemFlowService;
use App\Services\SolicitacaoBemPendenciaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SolicitacaoBemTiFlowTest extends TestCase
{
    private const TEST_DATABASE = 'cadastros_plansul_codex_test';

    protected function setUp(): void
    {
        parent::setUp();

        $mysqlConnection = (array) config('database.connections.mysql', []);
        $this->ensureTestDatabaseExists($mysqlConnection, self::TEST_DATABASE);

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', self::TEST_DATABASE);
        Config::set('queue.default', 'sync');
        Config::set('solicitacoes_bens.notificacoes.enabled', false);

        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckSessionExpiration::class,
            \App\Http\Middleware\AutoSyncKinghost::class,
            \App\Http\Middleware\EnsureProfileIsComplete::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $this->createSchema();
        SolicitacaoBem::forgetCachedColumnSupport();
        $this->seedTelaCatalog();
    }

    public function test_cria_solicitacao_manual_usa_fluxo_ti_quando_local_esta_classificado_para_ti(): void
    {
        $solicitante = $this->createUser('200001', 'LUIZ', 'LUIS EDUARDO MACHADO CHODREN', [User::TELA_SOLICITACOES_CRIAR]);
        $this->seedFuncionario('200002', 'RECEBEDOR TESTE');
        $projeto = $this->createProjeto();
        $local = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);

        $response = $this->actingAs($solicitante)->post(route('solicitacoes-bens.store'), [
            'solicitante_nome' => $solicitante->NOMEUSER,
            'solicitante_matricula' => $solicitante->CDMATRFUNCIONARIO,
            'recebedor_matricula' => '200002',
            'projeto_id' => $projeto->id,
            'local_projeto_id' => $local->id,
            'local_destino' => $local->delocal,
            'uf' => 'SC',
            'itens' => [
                [
                    'descricao' => 'Notebook',
                    'quantidade' => 1,
                    'unidade' => 'un',
                    'observacao' => 'Uso interno',
                ],
            ],
        ]);

        $response->assertRedirect(route('solicitacoes-bens.index'));
        $this->assertDatabaseHas('solicitacoes_bens', [
            'solicitante_matricula' => '200001',
            'local_projeto_id' => $local->id,
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_TI,
            'status' => SolicitacaoBem::STATUS_PENDENTE,
        ]);
    }

    public function test_cria_solicitacao_manual_ignora_origem_informada_e_usa_fluxo_do_local(): void
    {
        $solicitante = $this->createUser('200011', 'SOLICITANTE.TI', 'SOLICITANTE TI', [User::TELA_SOLICITACOES_CRIAR]);
        $this->seedFuncionario('200012', 'RECEBEDOR TI');
        $projeto = $this->createProjeto('PRJ-TI');
        $local = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);

        $response = $this->actingAs($solicitante)->post(route('solicitacoes-bens.store'), [
            'solicitante_nome' => $solicitante->NOMEUSER,
            'solicitante_matricula' => $solicitante->CDMATRFUNCIONARIO,
            'recebedor_matricula' => '200012',
            'projeto_id' => $projeto->id,
            'local_projeto_id' => $local->id,
            'local_destino' => $local->delocal,
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_PADRAO,
            'uf' => 'SC',
            'itens' => [
                [
                    'descricao' => 'Celular corporativo',
                    'quantidade' => 1,
                    'unidade' => 'un',
                    'observacao' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('solicitacoes-bens.index'));
        $this->assertDatabaseHas('solicitacoes_bens', [
            'solicitante_matricula' => '200011',
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_TI,
        ]);
    }

    public function test_edicao_do_solicitante_recalcula_fluxo_quando_local_muda(): void
    {
        $solicitante = $this->createUser('200021', 'SOLICITANTE.EDIT', 'SOLICITANTE EDICAO', [
            User::TELA_SOLICITACOES_CRIAR,
            User::TELA_SOLICITACOES_ATUALIZAR,
        ]);
        $this->seedFuncionario('200022', 'RECEBEDOR EDICAO');
        $projeto = $this->createProjeto('PRJ-EDIT');
        $localPadrao = $this->createLocal($projeto, 'ALMOXARIFADO', LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);
        $localTi = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);

        $solicitacao = $this->createSolicitacao($solicitante, $projeto, $localPadrao, LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);

        $response = $this->actingAs($solicitante)->put(route('solicitacoes-bens.update', $solicitacao), [
            'owner_edit' => '1',
            'solicitante_nome' => $solicitante->NOMEUSER,
            'recebedor_matricula' => '200022',
            'projeto_id' => $projeto->id,
            'local_projeto_id' => $localTi->id,
            'local_destino' => $localTi->delocal,
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_PADRAO,
            'observacao' => 'Atualizado para o estoque da TI.',
            'itens' => [
                [
                    'descricao' => 'Notebook atualizado',
                    'quantidade' => 1,
                    'unidade' => 'un',
                    'observacao' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('solicitacoes-bens.index'));
        $this->assertDatabaseHas('solicitacoes_bens', [
            'id' => $solicitacao->id,
            'local_projeto_id' => $localTi->id,
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_TI,
        ]);
    }

    public function test_triagem_inicial_ti_fica_apenas_com_bruno(): void
    {
        $bruno = $this->createUser('11829', 'BRUNO', 'BRUNO DE AZEVEDO FELICIANO', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $tiago = $this->createUser('185895', 'TIAGOP', 'TIAGO PACHECO', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $beatriz = $this->createUser('182687', 'BEA.SC', 'BEATRIZ PATRICIA VIRISSIMO DOS SANTOS', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $solicitante = $this->createUser('210001', 'SOLIC.IT', 'SOLICITANTE TI', [User::TELA_SOLICITACOES_CRIAR]);
        $projeto = $this->createProjeto('PRJ-TRIAGEM-TI');
        $local = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);

        $solicitacaoTi = $this->createSolicitacao($solicitante, $projeto, $local, LocalProjeto::FLUXO_RESPONSAVEL_TI);
        $flowService = app(SolicitacaoBemFlowService::class);

        $this->assertTrue($flowService->canConfirmSolicitacao($bruno, $solicitacaoTi));
        $this->assertFalse($flowService->canConfirmSolicitacao($tiago, $solicitacaoTi));
        $this->assertFalse($flowService->canConfirmSolicitacao($beatriz, $solicitacaoTi));
    }

    public function test_fluxo_padrao_mantem_triagem_com_tiago_e_beatriz(): void
    {
        $bruno = $this->createUser('11829', 'BRUNO', 'BRUNO DE AZEVEDO FELICIANO', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $tiago = $this->createUser('185895', 'TIAGOP', 'TIAGO PACHECO', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $beatriz = $this->createUser('182687', 'BEA.SC', 'BEATRIZ PATRICIA VIRISSIMO DOS SANTOS', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $solicitante = $this->createUser('220001', 'SOLIC.PADRAO', 'SOLICITANTE PADRÃO', [User::TELA_SOLICITACOES_CRIAR]);
        $projeto = $this->createProjeto('PRJ-PADRAO');
        $local = $this->createLocal($projeto, 'ALMOXARIFADO', LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);

        $solicitacaoPadrao = $this->createSolicitacao($solicitante, $projeto, $local, LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);
        $flowService = app(SolicitacaoBemFlowService::class);

        $this->assertFalse($flowService->canConfirmSolicitacao($bruno, $solicitacaoPadrao));
        $this->assertTrue($flowService->canConfirmSolicitacao($tiago, $solicitacaoPadrao));
        $this->assertTrue($flowService->canConfirmSolicitacao($beatriz, $solicitacaoPadrao));
    }

    public function test_pendencias_respeitam_o_fluxo_da_solicitacao(): void
    {
        $bruno = $this->createUser('11829', 'BRUNO', 'BRUNO DE AZEVEDO FELICIANO', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $tiago = $this->createUser('185895', 'TIAGOP', 'TIAGO PACHECO', [User::TELA_SOLICITACOES_TRIAGEM_INICIAL]);
        $solicitante = $this->createUser('230001', 'SOLIC.PEND', 'SOLICITANTE PENDÊNCIA', [User::TELA_SOLICITACOES_CRIAR]);
        $projeto = $this->createProjeto('PRJ-PEND');
        $localTi = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);
        $localPadrao = $this->createLocal($projeto, 'ALMOXARIFADO', LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);

        $solicitacaoTi = $this->createSolicitacao($solicitante, $projeto, $localTi, LocalProjeto::FLUXO_RESPONSAVEL_TI);
        $solicitacaoPadrao = $this->createSolicitacao($solicitante, $projeto, $localPadrao, LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);

        $service = app(SolicitacaoBemPendenciaService::class);
        $notificacoesBruno = $service->notificationsFor($bruno);
        $notificacoesTiago = $service->notificationsFor($tiago);

        $this->assertSame(
            ['solicitacao:' . $solicitacaoTi->id . ':confirmar_solicitacao'],
            array_values(array_column($notificacoesBruno, 'item_key'))
        );

        $this->assertSame(
            ['solicitacao:' . $solicitacaoPadrao->id . ':confirmar_solicitacao'],
            array_values(array_column($notificacoesTiago, 'item_key'))
        );
    }

    public function test_liberacao_exige_autorizacao_do_theo_antes_da_etapa_final_do_bruno(): void
    {
        $theo = $this->createUser('134616', 'THEO', 'THEODORO BUZZI AVILA', [User::TELA_SOLICITACOES_AUTORIZACAO_LIBERACAO]);
        $bruno = $this->createUser('11829', 'BRUNO', 'BRUNO DE AZEVEDO FELICIANO', [User::TELA_SOLICITACOES_LIBERACAO_ENVIO]);
        $solicitante = $this->createUser('230777', 'SOLIC.LIB', 'SOLICITANTE LIBERAÇÃO', [User::TELA_SOLICITACOES_CRIAR]);
        $projeto = $this->createProjeto('PRJ-LIB');
        $local = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);

        $solicitacao = $this->createSolicitacao(
            $solicitante,
            $projeto,
            $local,
            LocalProjeto::FLUXO_RESPONSAVEL_TI,
            SolicitacaoBem::STATUS_LIBERACAO
        );

        $solicitacao->forceFill([
            'quote_options_payload' => [[
                'transporter' => 'Transportadora Teste',
                'amount' => 150.25,
                'deadline' => '3 dias úteis',
                'tracking_type' => SolicitacaoBem::TRACKING_TYPE_RASTREIO,
                'notes' => 'Cotação de teste',
            ]],
            'quote_registered_at' => now(),
            'release_authorized_at' => null,
            'quote_approved_at' => null,
        ])->save();

        $service = app(SolicitacaoBemPendenciaService::class);

        $this->assertSame(
            ['solicitacao:' . $solicitacao->id . ':autorizar_liberacao'],
            array_values(array_column($service->notificationsFor($theo), 'item_key'))
        );
        $this->assertSame([], array_values(array_column($service->notificationsFor($bruno), 'item_key')));

        $solicitacao->forceFill([
            'release_authorized_by_id' => $theo->getAuthIdentifier(),
            'release_authorized_at' => now(),
        ])->save();

        $this->assertSame([], array_values(array_column($service->notificationsFor($theo), 'item_key')));
        $this->assertSame(
            ['solicitacao:' . $solicitacao->id . ':liberar_envio'],
            array_values(array_column($service->notificationsFor($bruno), 'item_key'))
        );
    }

    public function test_reenvio_de_cancelada_preserva_fluxo_responsavel(): void
    {
        $solicitante = $this->createUser('240001', 'SOLIC.REENVIO', 'SOLICITANTE REENVIO', [User::TELA_SOLICITACOES_CRIAR]);
        $admin = $this->createAdmin('900001', 'ADMIN.REENVIO', 'ADMIN REENVIO');
        $projeto = $this->createProjeto('PRJ-REENVIO');
        $local = $this->createLocal($projeto, 'ESTOQUE TI', LocalProjeto::FLUXO_RESPONSAVEL_TI);
        $solicitacao = $this->createSolicitacao(
            $solicitante,
            $projeto,
            $local,
            LocalProjeto::FLUXO_RESPONSAVEL_TI,
            SolicitacaoBem::STATUS_CANCELADO
        );

        $response = $this->actingAs($admin)->postJson(route('solicitacoes-bens.recreate-cancelled', $solicitacao), [
            'motivo_reenvio' => 'Correção do pedido para registrar a saída.',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $novaSolicitacaoId = (int) $response->json('nova_solicitacao_id');
        $this->assertDatabaseHas('solicitacoes_bens', [
            'id' => $novaSolicitacaoId,
            'status' => SolicitacaoBem::STATUS_PENDENTE,
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_TI,
            'local_projeto_id' => $local->id,
        ]);
    }

    private function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'solicitacao_bens_itens',
            'solicitacoes_bens',
            'locais_projeto',
            'tabfant',
            'acessousuario',
            'acessotela',
            'funcionarios',
            'usuario',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        Schema::create('usuario', function (Blueprint $table) {
            $table->bigIncrements('NUSEQUSUARIO');
            $table->string('NOMEUSER', 120)->nullable();
            $table->string('NMLOGIN', 60)->nullable();
            $table->string('CDMATRFUNCIONARIO', 20)->nullable();
            $table->string('PERFIL', 10)->default(User::PERFIL_USUARIO);
            $table->string('SENHA', 255);
            $table->string('LGATIVO', 1)->default('S');
            $table->string('UF', 2)->nullable();
            $table->string('email', 200)->nullable();
            $table->boolean('must_change_password')->nullable();
            $table->boolean('needs_identity_update')->nullable();
            $table->string('theme', 20)->nullable();
        });

        Schema::create('funcionarios', function (Blueprint $table) {
            $table->string('CDMATRFUNCIONARIO', 20)->primary();
            $table->string('NMFUNCIONARIO', 150);
        });

        Schema::create('acessotela', function (Blueprint $table) {
            $table->unsignedInteger('NUSEQTELA')->primary();
            $table->string('FLACESSO', 1)->default('S');
        });

        Schema::create('acessousuario', function (Blueprint $table) {
            $table->unsignedInteger('NUSEQTELA');
            $table->string('CDMATRFUNCIONARIO', 20);
            $table->string('INACESSO', 1)->default('S');
        });

        Schema::create('tabfant', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('CDPROJETO', 30)->nullable();
            $table->string('NOMEPROJETO', 150)->nullable();
            $table->string('LOCAL', 150)->nullable();
            $table->string('UF', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('locais_projeto', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tabfant_id');
            $table->unsignedInteger('cdlocal')->nullable();
            $table->string('delocal', 150);
            $table->string('UF', 2)->nullable();
            $table->string('tipo_local', 30)->default(LocalProjeto::TIPO_LOCAL_PADRAO);
            $table->string('fluxo_responsavel', 20)->default(LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);
            $table->timestamps();
        });

        Schema::create('solicitacoes_bens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('solicitante_id')->nullable();
            $table->string('solicitante_nome', 120)->nullable();
            $table->string('solicitante_matricula', 20)->nullable();
            $table->string('email_origem', 200)->nullable();
            $table->string('email_assunto', 200)->nullable();
            $table->unsignedInteger('projeto_id')->nullable();
            $table->unsignedBigInteger('local_projeto_id')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('setor', 120)->nullable();
            $table->string('local_destino', 150)->nullable();
            $table->string('fluxo_responsavel', 20)->nullable();
            $table->string('status', 30)->default(SolicitacaoBem::STATUS_PENDENTE);
            $table->text('observacao')->nullable();
            $table->text('observacao_controle')->nullable();
            $table->string('matricula_recebedor', 20)->nullable();
            $table->string('nome_recebedor', 120)->nullable();
            $table->string('destination_type', 20)->nullable();
            $table->text('justificativa_cancelamento')->nullable();
            $table->string('tracking_code', 120)->nullable();
            $table->decimal('logistics_height_cm', 10, 2)->nullable();
            $table->decimal('logistics_width_cm', 10, 2)->nullable();
            $table->decimal('logistics_length_cm', 10, 2)->nullable();
            $table->decimal('logistics_weight_kg', 10, 3)->nullable();
            $table->unsignedInteger('logistics_volume_count')->nullable();
            $table->string('logistics_asset_number', 50)->nullable();
            $table->text('logistics_notes')->nullable();
            $table->timestamp('logistics_registered_at')->nullable();
            $table->string('quote_transporter', 120)->nullable();
            $table->decimal('quote_amount', 12, 2)->nullable();
            $table->string('quote_deadline', 80)->nullable();
            $table->text('quote_notes')->nullable();
            $table->longText('quote_options_payload')->nullable();
            $table->unsignedTinyInteger('quote_selected_index')->nullable();
            $table->string('quote_tracking_type', 30)->nullable();
            $table->timestamp('quote_registered_at')->nullable();
            $table->unsignedBigInteger('release_authorized_by_id')->nullable();
            $table->timestamp('release_authorized_at')->nullable();
            $table->timestamp('quote_approved_at')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitacao_bens_itens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('solicitacao_id');
            $table->string('descricao', 200);
            $table->integer('quantidade')->default(1);
            $table->string('unidade', 20)->nullable();
            $table->string('observacao', 500)->nullable();
            $table->timestamps();
        });
    }

    private function seedTelaCatalog(): void
    {
        foreach ([1000, 1006, 1010, 1012, 1013, 1014, 1019, 1020, 1021] as $tela) {
            DB::table('acessotela')->insert([
                'NUSEQTELA' => $tela,
                'FLACESSO' => 'S',
            ]);
        }
    }

    private function seedFuncionario(string $matricula, string $nome): void
    {
        Funcionario::query()->updateOrCreate(
            ['CDMATRFUNCIONARIO' => $matricula],
            ['NMFUNCIONARIO' => $nome]
        );
    }

    private function createUser(string $matricula, string $login, string $nome, array $telas = []): User
    {
        $this->seedFuncionario($matricula, $nome);

        $user = User::create([
            'NOMEUSER' => $nome,
            'NMLOGIN' => $login,
            'CDMATRFUNCIONARIO' => $matricula,
            'PERFIL' => User::PERFIL_USUARIO,
            'SENHA' => 'Plansul@123456',
            'LGATIVO' => 'S',
            'UF' => 'SC',
            'email' => strtolower(str_replace(' ', '', $login)) . '@plansul.com.br',
            'must_change_password' => false,
            'needs_identity_update' => false,
        ]);

        foreach ($telas as $tela) {
            AcessoUsuario::create([
                'CDMATRFUNCIONARIO' => $matricula,
                'NUSEQTELA' => $tela,
                'INACESSO' => 'S',
            ]);
        }

        return $user->fresh();
    }

    private function createAdmin(string $matricula, string $login, string $nome): User
    {
        $this->seedFuncionario($matricula, $nome);

        return User::create([
            'NOMEUSER' => $nome,
            'NMLOGIN' => $login,
            'CDMATRFUNCIONARIO' => $matricula,
            'PERFIL' => User::PERFIL_ADMIN,
            'SENHA' => 'Plansul@123456',
            'LGATIVO' => 'S',
            'UF' => 'SC',
            'email' => strtolower(str_replace(' ', '', $login)) . '@plansul.com.br',
            'must_change_password' => false,
            'needs_identity_update' => false,
        ])->fresh();
    }

    private function createProjeto(string $codigo = 'PRJ-001'): Tabfant
    {
        $id = Tabfant::query()->count() + 1;

        return Tabfant::create([
            'id' => $id,
            'CDPROJETO' => $codigo,
            'NOMEPROJETO' => 'Projeto ' . $codigo,
            'UF' => 'SC',
        ]);
    }

    private function createLocal(
        Tabfant $projeto,
        string $nome,
        string $fluxoResponsavel,
        string $tipoLocal = LocalProjeto::TIPO_LOCAL_PADRAO
    ): LocalProjeto {
        return LocalProjeto::create([
            'tabfant_id' => $projeto->id,
            'cdlocal' => LocalProjeto::query()->count() + 1,
            'delocal' => $nome,
            'UF' => 'SC',
            'tipo_local' => $tipoLocal,
            'fluxo_responsavel' => $fluxoResponsavel,
        ]);
    }

    private function createSolicitacao(
        User $solicitante,
        Tabfant $projeto,
        LocalProjeto $local,
        string $fluxoResponsavel,
        string $status = SolicitacaoBem::STATUS_PENDENTE
    ): SolicitacaoBem {
        $solicitacao = SolicitacaoBem::create([
            'solicitante_id' => $solicitante->getAuthIdentifier(),
            'solicitante_nome' => $solicitante->NOMEUSER,
            'solicitante_matricula' => $solicitante->CDMATRFUNCIONARIO,
            'projeto_id' => $projeto->id,
            'local_projeto_id' => $local->id,
            'uf' => 'SC',
            'local_destino' => $local->delocal,
            'fluxo_responsavel' => $fluxoResponsavel,
            'status' => $status,
            'matricula_recebedor' => $solicitante->CDMATRFUNCIONARIO,
            'nome_recebedor' => $solicitante->NOMEUSER,
            'destination_type' => SolicitacaoBem::DESTINATION_PROJETO,
        ]);

        $solicitacao->itens()->create([
            'descricao' => 'Item de teste',
            'quantidade' => 1,
            'unidade' => 'un',
            'observacao' => null,
        ]);

        return $solicitacao->fresh();
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function ensureTestDatabaseExists(array $connection, string $database): void
    {
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');
        $charset = (string) ($connection['charset'] ?? 'utf8mb4');

        if (!empty($connection['unix_socket'])) {
            $dsn = sprintf(
                'mysql:unix_socket=%s;charset=%s',
                (string) $connection['unix_socket'],
                $charset
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;charset=%s',
                (string) ($connection['host'] ?? '127.0.0.1'),
                (string) ($connection['port'] ?? '3306'),
                $charset
            );
        }

        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $database
        ));
    }
}
