<?php

namespace Tests\Feature;

use App\Helpers\MenuHelper;
use App\Models\AcessoUsuario;
use App\Models\Funcionario;
use App\Models\LocalProjeto;
use App\Models\RegistroRemovido;
use App\Models\SolicitacaoBem;
use App\Models\Tabfant;
use App\Models\User;
use App\Services\SystemNewsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemPerformanceSafetyTest extends TestCase
{
    private const TEST_DATABASE = 'cadastros_plansul_codex_system_perf_test';

    protected function setUp(): void
    {
        parent::setUp();

        $mysqlConnection = (array) config('database.connections.mysql', []);
        $this->ensureTestDatabaseExists($mysqlConnection, self::TEST_DATABASE);

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', self::TEST_DATABASE);
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('novidades.enabled', true);
        Config::set('novidades.items', [
            [
                'key' => 'teste-novidade',
                'title' => 'Nova pendencia no topo',
                'summary' => 'Resumo da novidade.',
                'released_at' => '2026-03-25 10:00:00',
                'active' => true,
            ],
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckSessionExpiration::class,
            \App\Http\Middleware\AutoSyncKinghost::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $this->createSchema();
        SolicitacaoBem::forgetCachedColumnSupport();
        $this->seedTelaCatalog();
    }

    public function test_regras_implicitas_de_permissao_sao_preservadas(): void
    {
        $usuarioBase = $this->createUser('310001', 'USUARIO.BASE', 'USUARIO BASE');
        $this->assertTrue($usuarioBase->temAcessoTela(User::TELA_PATRIMONIO));
        $this->assertTrue($usuarioBase->temAcessoTela(User::TELA_RELATORIOS));
        $this->assertFalse($usuarioBase->temAcessoTela(User::TELA_SOLICITACOES_BENS));

        $usuarioSolicitacao = $this->createUser('310002', 'USUARIO.SOL', 'USUARIO SOL', [
            User::TELA_SOLICITACOES_CRIAR,
        ]);
        $this->assertTrue($usuarioSolicitacao->temAcessoTela(User::TELA_SOLICITACOES_BENS));

        $consultor = $this->createUser('310003', 'CONSULTOR', 'USUARIO CONSULTOR', [], User::PERFIL_CONSULTOR);
        $this->assertTrue($consultor->temAcessoTela(User::TELA_SOLICITACOES_BENS));
    }

    public function test_menu_helper_mantem_telas_visiveis_do_usuario(): void
    {
        $usuario = $this->createUser('320001', 'MENU.USER', 'MENU USER', [
            User::TELA_SOLICITACOES_CRIAR,
            '1002',
        ]);

        Auth::login($usuario);

        $telasMenu = MenuHelper::getTelasParaMenu();

        $this->assertArrayHasKey('1000', $telasMenu);
        $this->assertArrayHasKey('1002', $telasMenu);
        $this->assertArrayHasKey(User::TELA_SOLICITACOES_BENS, $telasMenu);
        $this->assertArrayNotHasKey('1003', $telasMenu);
    }

    public function test_menu_principal_renderiza_apenas_cards_esperados(): void
    {
        $usuario = $this->createUser('320010', 'MENU.ROTA', 'MENU ROTA', [
            '1002',
            User::TELA_SOLICITACOES_CRIAR,
        ]);

        $response = $this->actingAs($usuario)->get(route('menu.index'));

        $response->assertOk();
        $response->assertSee('Controle de Patrimônio');
        $response->assertSee('Controle de Estoque');
        $response->assertDontSee('Visitante');
    }

    public function test_endpoint_de_notificacoes_importantes_retorna_payload_padrao(): void
    {
        $usuario = $this->createUser('330001', 'REMOVIDOS.USER', 'REMOVIDOS USER', ['1009']);

        RegistroRemovido::create([
            'entity' => 'Patrimonio',
            'model_type' => 'App\\Models\\Patrimonio',
            'model_id' => 1,
            'model_label' => 'Patrimonio 1',
            'deleted_by' => 'Teste',
            'deleted_at' => now(),
            'payload' => [],
        ]);

        $response = $this->actingAs($usuario)->getJson(route('api.notificacoes.importantes'));

        $response->assertOk();
        $response->assertJsonStructure([
            'items',
            'total_count',
            'grouped',
            'generated_at',
        ]);
        $this->assertSame(1, $response->json('total_count'));
    }

    public function test_registro_de_novidade_invalida_cache_do_payload(): void
    {
        $usuario = $this->createUser('340001', 'NEWS.USER', 'NEWS USER');
        $service = app(SystemNewsService::class);

        $antes = $service->payloadForUser($usuario);
        $this->assertSame(['teste-novidade'], $antes['unseen_keys']);

        $response = $this->actingAs($usuario)->postJson(route('api.novidades-sistema.visualizar'), [
            'keys' => ['teste-novidade'],
        ]);

        $response->assertOk()->assertJson([
            'message' => 'Novidades registradas com sucesso.',
        ]);

        $this->assertDatabaseHas('novidades_sistema_visualizacoes', [
            'usuario_id' => $usuario->getAuthIdentifier(),
            'novidade_key' => 'teste-novidade',
        ]);

        $depois = $service->payloadForUser($usuario);
        $this->assertSame([], $depois['unseen_keys']);
        $this->assertSame(0, $depois['unseen_count']);
    }

    public function test_notificacoes_de_solicitacoes_funcionam_sem_coluna_de_autorizacao_theo(): void
    {
        $usuario = $this->createUser('11829', 'BRUNO', 'BRUNO DE AZEVEDO FELICIANO', [
            User::TELA_SOLICITACOES_LIBERACAO_ENVIO,
        ]);
        $projeto = $this->createProjeto('3600');
        $local = $this->createLocal($projeto, 'ESTOQUE CENTRAL', LocalProjeto::FLUXO_RESPONSAVEL_TI);

        SolicitacaoBem::forgetCachedColumnSupport();

        $solicitacao = SolicitacaoBem::create([
            'solicitante_id' => $usuario->getAuthIdentifier(),
            'solicitante_nome' => $usuario->NOMEUSER,
            'solicitante_matricula' => $usuario->CDMATRFUNCIONARIO,
            'projeto_id' => $projeto->id,
            'local_projeto_id' => $local->id,
            'uf' => 'SC',
            'local_destino' => $local->delocal,
            'fluxo_responsavel' => $local->fluxo_responsavel,
            'status' => SolicitacaoBem::STATUS_LIBERACAO,
            'destination_type' => SolicitacaoBem::DESTINATION_PROJETO,
            'quote_options_payload' => [[
                'transporter' => 'Transportadora Teste',
                'amount' => 199.90,
                'deadline' => '3 dias',
                'tracking_type' => SolicitacaoBem::TRACKING_TYPE_RASTREIO,
            ]],
            'quote_approved_at' => null,
        ]);

        $response = $this->actingAs($usuario)->getJson(route('api.notificacoes.importantes'));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertJsonPath('items.0.provider', 'solicitacoes');
        $response->assertJsonPath('items.0.item_key', 'solicitacao:' . $solicitacao->id . ':liberar_envio');
    }

    public function test_rotas_principais_renderizam_sem_erro_para_usuario_comum(): void
    {
        $usuario = $this->createUser('350001', 'ROTAS.USER', 'ROTAS USER', [
            User::TELA_PATRIMONIO,
            '1002',
            User::TELA_SOLICITACOES_CRIAR,
        ]);

        $projeto = $this->createProjeto('3500');
        $local = $this->createLocal($projeto, 'ESTOQUE CENTRAL', LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);
        $this->createPatrimonio($usuario, $projeto, $local);
        $this->createSolicitacao($usuario, $projeto, $local);

        $this->actingAs($usuario)->get(route('patrimonios.index'))->assertOk();
        $this->actingAs($usuario)->get(route('projetos.index'))->assertOk();
        $this->actingAs($usuario)->get(route('solicitacoes-bens.index'))->assertOk();
    }

    private function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'novidades_sistema_visualizacoes',
            'registros_removidos',
            'OBJETOPATR',
            'patr',
            'solicitacoes_bens_status_historico',
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
            $table->unsignedInteger('password_policy_version')->nullable();
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

        Schema::create('solicitacoes_bens_status_historico', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('solicitacao_id');
            $table->string('status_anterior', 30)->nullable();
            $table->string('status_novo', 30);
            $table->string('acao', 80)->nullable();
            $table->text('motivo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
        });

        Schema::create('patr', function (Blueprint $table) {
            $table->unsignedInteger('NUSEQPATR')->primary();
            $table->unsignedInteger('NUPATRIMONIO')->nullable();
            $table->string('SITUACAO', 50)->nullable();
            $table->string('TIPO', 50)->nullable();
            $table->string('MARCA', 100)->nullable();
            $table->string('MODELO', 100)->nullable();
            $table->string('CARACTERISTICAS', 255)->nullable();
            $table->string('DIMENSAO', 100)->nullable();
            $table->string('COR', 50)->nullable();
            $table->string('NUSERIE', 100)->nullable();
            $table->unsignedInteger('CDLOCAL')->nullable();
            $table->date('DTAQUISICAO')->nullable();
            $table->date('DTBAIXA')->nullable();
            $table->date('DTGARANTIA')->nullable();
            $table->text('DEHISTORICO')->nullable();
            $table->date('DTLAUDO')->nullable();
            $table->string('DEPATRIMONIO', 255)->nullable();
            $table->string('CDMATRFUNCIONARIO', 20)->nullable();
            $table->string('CDMATRGERENTE', 20)->nullable();
            $table->string('CDLOCALINTERNO', 20)->nullable();
            $table->string('CDPROJETO', 30)->nullable();
            $table->string('USUARIO', 60)->nullable();
            $table->date('DTOPERACAO')->nullable();
            $table->string('FLCONFERIDO', 1)->nullable();
            $table->string('NUMOF', 50)->nullable();
            $table->unsignedInteger('CODOBJETO')->nullable();
            $table->string('NMPLANTA', 100)->nullable();
            $table->decimal('PESO', 10, 2)->nullable();
            $table->string('TAMANHO', 50)->nullable();
            $table->string('VOLTAGEM', 50)->nullable();
            $table->string('UF', 2)->nullable();
        });

        Schema::create('OBJETOPATR', function (Blueprint $table) {
            $table->unsignedInteger('NUSEQOBJETO')->primary();
            $table->unsignedInteger('NUSEQTIPOPATR')->nullable();
            $table->string('DEOBJETO', 150);
        });

        Schema::create('registros_removidos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('entity', 80)->nullable();
            $table->string('model_type', 150)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_label', 150)->nullable();
            $table->string('deleted_by', 120)->nullable();
            $table->string('deleted_by_matricula', 20)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('request_path', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('novidades_sistema_visualizacoes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usuario_id');
            $table->string('novidade_key', 120);
            $table->timestamp('visualizado_em')->nullable();
            $table->timestamps();
            $table->unique(['usuario_id', 'novidade_key']);
        });
    }

    private function seedTelaCatalog(): void
    {
        foreach ([1000, 1002, 1003, 1004, 1006, 1007, 1009, 1010, 1011, 1012, 1013, 1014, 1016, 1019, 1020] as $tela) {
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

    /**
     * @param array<int, string> $telas
     */
    private function createUser(string $matricula, string $login, string $nome, array $telas = [], string $perfil = User::PERFIL_USUARIO): User
    {
        $this->seedFuncionario($matricula, $nome);

        $user = User::create([
            'NOMEUSER' => $nome,
            'NMLOGIN' => $login,
            'CDMATRFUNCIONARIO' => $matricula,
            'PERFIL' => $perfil,
            'SENHA' => 'Plansul@123456',
            'LGATIVO' => 'S',
            'UF' => 'SC',
            'email' => strtolower(str_replace(' ', '', $login)) . '@plansul.com.br',
            'must_change_password' => false,
            'password_policy_version' => 1,
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

    private function createLocal(Tabfant $projeto, string $nome, string $fluxo): LocalProjeto
    {
        return LocalProjeto::create([
            'tabfant_id' => $projeto->id,
            'cdlocal' => LocalProjeto::query()->count() + 1,
            'delocal' => $nome,
            'UF' => 'SC',
            'tipo_local' => LocalProjeto::TIPO_LOCAL_PADRAO,
            'fluxo_responsavel' => $fluxo,
        ]);
    }

    private function createSolicitacao(User $usuario, Tabfant $projeto, LocalProjeto $local): SolicitacaoBem
    {
        $solicitacao = SolicitacaoBem::create([
            'solicitante_id' => $usuario->getAuthIdentifier(),
            'solicitante_nome' => $usuario->NOMEUSER,
            'solicitante_matricula' => $usuario->CDMATRFUNCIONARIO,
            'projeto_id' => $projeto->id,
            'local_projeto_id' => $local->id,
            'uf' => 'SC',
            'local_destino' => $local->delocal,
            'fluxo_responsavel' => $local->fluxo_responsavel,
            'status' => SolicitacaoBem::STATUS_PENDENTE,
            'matricula_recebedor' => $usuario->CDMATRFUNCIONARIO,
            'nome_recebedor' => $usuario->NOMEUSER,
            'destination_type' => SolicitacaoBem::DESTINATION_PROJETO,
        ]);

        $solicitacao->itens()->create([
            'descricao' => 'Item de teste',
            'quantidade' => 1,
            'unidade' => 'un',
        ]);

        return $solicitacao->fresh();
    }

    private function createPatrimonio(User $usuario, Tabfant $projeto, LocalProjeto $local): void
    {
        DB::table('patr')->insert([
            'NUSEQPATR' => 1,
            'NUPATRIMONIO' => 1001,
            'SITUACAO' => 'EM USO',
            'MARCA' => 'DELL',
            'MODELO' => 'LATITUDE',
            'CDLOCAL' => $local->cdlocal,
            'DTAQUISICAO' => '2026-01-01',
            'DEPATRIMONIO' => 'NOTEBOOK',
            'CDMATRFUNCIONARIO' => $usuario->CDMATRFUNCIONARIO,
            'CDMATRGERENTE' => $usuario->CDMATRFUNCIONARIO,
            'CDPROJETO' => $projeto->CDPROJETO,
            'USUARIO' => $usuario->NMLOGIN,
            'DTOPERACAO' => '2026-01-02',
            'FLCONFERIDO' => 'S',
            'NUMOF' => 'OF-1',
            'CODOBJETO' => 1,
            'UF' => 'SC',
        ]);

        DB::table('OBJETOPATR')->updateOrInsert(
            ['NUSEQOBJETO' => 1],
            ['NUSEQTIPOPATR' => 1, 'DEOBJETO' => 'NOTEBOOK']
        );
    }

    /**
     * @param array<string, mixed> $connection
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
