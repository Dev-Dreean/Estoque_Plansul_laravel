<?php

namespace Tests\Feature;

use App\Models\AcessoUsuario;
use App\Models\Funcionario;
use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use App\Models\Tabfant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatrimonioNumeroMesaTest extends TestCase
{
    private const TEST_DATABASE = 'cadastros_plansul_codex_num_mesa_test';

    protected function setUp(): void
    {
        parent::setUp();

        $mysqlConnection = (array) config('database.connections.mysql', []);
        $this->ensureTestDatabaseExists($mysqlConnection, self::TEST_DATABASE);

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', self::TEST_DATABASE);
        Config::set('queue.default', 'sync');
        Config::set('cache.default', 'array');

        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckSessionExpiration::class,
            \App\Http\Middleware\AutoSyncKinghost::class,
            \App\Http\Middleware\EnsureProfileIsComplete::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $this->createSchema();
        $this->seedTelaCatalog();
        $this->seedObjetoPadrao();
    }

    public function test_cria_patrimonio_em_uso_com_numero_de_mesa_valido(): void
    {
        $usuario = $this->createUser('410001', 'MESA.USER', 'USUARIO MESA', [User::TELA_PATRIMONIO]);
        $projeto = $this->createProjeto(5001);
        $local = $this->createLocal($projeto, 101, 'ADMINISTRATIVO');

        $response = $this->actingAs($usuario)->post(route('patrimonios.store'), $this->payloadBase($projeto, $local, [
            'NUPATRIMONIO' => 900101,
            'NUMMESA' => 'MESA-01',
            'SITUACAO' => 'EM USO',
        ]));

        $response->assertRedirect(route('patrimonios.index'));
        $this->assertDatabaseHas('patr', [
            'NUPATRIMONIO' => 900101,
            'NUMMESA' => 'MESA-01',
            'SITUACAO' => 'EM USO',
        ]);
    }

    public function test_bloqueia_numero_de_mesa_repetido_para_outro_patrimonio_em_uso(): void
    {
        $usuario = $this->createUser('410002', 'MESA.DUP', 'USUARIO DUPLICADO', [User::TELA_PATRIMONIO]);
        $projeto = $this->createProjeto(5002);
        $local = $this->createLocal($projeto, 102, 'ADMINISTRATIVO');

        $this->createPatrimonio($usuario, $projeto, $local, [
            'NUPATRIMONIO' => 900201,
            'NUMMESA' => 'MESA-02',
            'SITUACAO' => 'EM USO',
        ]);

        $response = $this->from(route('patrimonios.create'))
            ->actingAs($usuario)
            ->post(route('patrimonios.store'), $this->payloadBase($projeto, $local, [
                'NUPATRIMONIO' => 900202,
                'NUMMESA' => 'MESA-02',
                'SITUACAO' => 'EM USO',
            ]));

        $response->assertRedirect(route('patrimonios.create'));
        $response->assertSessionHasErrors('NUMMESA');
        $this->assertDatabaseMissing('patr', [
            'NUPATRIMONIO' => 900202,
        ]);
    }

    public function test_permite_editar_mantendo_o_proprio_numero_de_mesa(): void
    {
        $usuario = $this->createUser('410003', 'MESA.EDIT', 'USUARIO EDICAO', [User::TELA_PATRIMONIO]);
        $projeto = $this->createProjeto(5003);
        $local = $this->createLocal($projeto, 103, 'ADMINISTRATIVO');

        $patrimonio = $this->createPatrimonio($usuario, $projeto, $local, [
            'NUPATRIMONIO' => 900301,
            'NUMMESA' => 'MESA-03',
            'SITUACAO' => 'EM USO',
        ]);

        $response = $this->actingAs($usuario)->put(route('patrimonios.update', $patrimonio), $this->payloadBase($projeto, $local, [
            'NUPATRIMONIO' => 900301,
            'NUMMESA' => 'MESA-03',
            'MODELO' => 'LATITUDE 7450',
            'SITUACAO' => 'EM USO',
        ]));

        $response->assertRedirect(route('patrimonios.index'));
        $this->assertDatabaseHas('patr', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUMMESA' => 'MESA-03',
            'MODELO' => 'LATITUDE 7450',
        ]);
    }

    public function test_permite_reutilizar_numero_de_mesa_quando_outro_patrimonio_nao_esta_em_uso(): void
    {
        $usuario = $this->createUser('410004', 'MESA.REUSO', 'USUARIO REUSO', [User::TELA_PATRIMONIO]);
        $projeto = $this->createProjeto(5004);
        $local = $this->createLocal($projeto, 104, 'ADMINISTRATIVO');

        $this->createPatrimonio($usuario, $projeto, $local, [
            'NUPATRIMONIO' => 900401,
            'NUMMESA' => 'MESA-04',
            'SITUACAO' => 'BAIXA',
        ]);

        $response = $this->actingAs($usuario)->post(route('patrimonios.store'), $this->payloadBase($projeto, $local, [
            'NUPATRIMONIO' => 900402,
            'NUMMESA' => 'MESA-04',
            'SITUACAO' => 'EM USO',
        ]));

        $response->assertRedirect(route('patrimonios.index'));
        $this->assertDatabaseHas('patr', [
            'NUPATRIMONIO' => 900402,
            'NUMMESA' => 'MESA-04',
            'SITUACAO' => 'EM USO',
        ]);
    }

    public function test_filtro_por_numero_de_mesa_retorna_o_registro_correto(): void
    {
        $usuario = $this->createUser('410005', 'MESA.FILTRO', 'USUARIO FILTRO', [User::TELA_PATRIMONIO]);
        $projeto = $this->createProjeto(5005);
        $local = $this->createLocal($projeto, 105, 'ADMINISTRATIVO');

        $this->createPatrimonio($usuario, $projeto, $local, [
            'NUPATRIMONIO' => 900501,
            'NUMMESA' => 'MESA-05',
            'SITUACAO' => 'EM USO',
        ]);
        $this->createPatrimonio($usuario, $projeto, $local, [
            'NUPATRIMONIO' => 900502,
            'NUMMESA' => 'MESA-99',
            'SITUACAO' => 'EM USO',
        ]);

        $response = $this->actingAs($usuario)->get(route('patrimonios.index', [
            'num_mesa' => 'MESA-05',
        ]));

        $response->assertOk();
        $response->assertSee('900501');
        $response->assertSee('Mesa MESA-05');
        $response->assertDontSee('900502');
    }

    private function createSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'OBJETOPATR',
            'patr',
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

        Schema::create('patr', function (Blueprint $table) {
            $table->increments('NUSEQPATR');
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
            $table->dateTime('DTOPERACAO')->nullable();
            $table->string('FLCONFERIDO', 1)->nullable();
            $table->string('NUMOF', 50)->nullable();
            $table->unsignedInteger('CODOBJETO')->nullable();
            $table->string('NMPLANTA', 100)->nullable();
            $table->string('NUMMESA', 30)->nullable();
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
    }

    private function seedTelaCatalog(): void
    {
        foreach ([1000] as $tela) {
            DB::table('acessotela')->insert([
                'NUSEQTELA' => $tela,
                'FLACESSO' => 'S',
            ]);
        }
    }

    private function seedObjetoPadrao(): void
    {
        DB::table('OBJETOPATR')->insert([
            'NUSEQOBJETO' => 1,
            'NUSEQTIPOPATR' => 1,
            'DEOBJETO' => 'NOTEBOOK',
        ]);
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

    private function seedFuncionario(string $matricula, string $nome): void
    {
        Funcionario::query()->updateOrCreate(
            ['CDMATRFUNCIONARIO' => $matricula],
            ['NMFUNCIONARIO' => $nome]
        );
    }

    private function createProjeto(int $codigo): Tabfant
    {
        $id = (Tabfant::query()->max('id') ?? 0) + 1;

        return Tabfant::create([
            'id' => $id,
            'CDPROJETO' => (string) $codigo,
            'NOMEPROJETO' => 'Projeto ' . $codigo,
            'UF' => 'SC',
        ]);
    }

    private function createLocal(Tabfant $projeto, int $cdlocal, string $nome): LocalProjeto
    {
        return LocalProjeto::create([
            'tabfant_id' => $projeto->id,
            'cdlocal' => $cdlocal,
            'delocal' => $nome,
            'UF' => 'SC',
            'tipo_local' => LocalProjeto::TIPO_LOCAL_PADRAO,
            'fluxo_responsavel' => LocalProjeto::FLUXO_RESPONSAVEL_PADRAO,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function payloadBase(Tabfant $projeto, LocalProjeto $local, array $overrides = []): array
    {
        return array_merge([
            'NUPATRIMONIO' => 999001,
            'NUSEQOBJ' => 1,
            'SITUACAO' => 'EM USO',
            'NUMOF' => 123,
            'DEHISTORICO' => 'Patrimônio de teste',
            'CDPROJETO' => (int) $projeto->CDPROJETO,
            'CDLOCAL' => $local->id,
            'NMPLANTA' => null,
            'NUMMESA' => null,
            'MARCA' => 'DELL',
            'MODELO' => 'LATITUDE',
            'DTAQUISICAO' => '2026-04-10',
            'DTBAIXA' => null,
            'PESO' => 2.5,
            'TAMANHO' => '14 POL',
            'VOLTAGEM' => '220V',
            'FLCONFERIDO' => 'S',
            'CDMATRFUNCIONARIO' => null,
            'CDMATRGERENTE' => null,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createPatrimonio(User $usuario, Tabfant $projeto, LocalProjeto $local, array $overrides = []): Patrimonio
    {
        return Patrimonio::create(array_merge([
            'NUPATRIMONIO' => 999100,
            'CODOBJETO' => 1,
            'DEPATRIMONIO' => 'NOTEBOOK',
            'SITUACAO' => 'EM USO',
            'FLCONFERIDO' => 'S',
            'CDMATRFUNCIONARIO' => null,
            'CDMATRGERENTE' => null,
            'NUMOF' => 123,
            'DEHISTORICO' => 'Patrimônio base',
            'CDPROJETO' => $projeto->CDPROJETO,
            'CDLOCAL' => $local->cdlocal,
            'NMPLANTA' => null,
            'NUMMESA' => null,
            'MARCA' => 'DELL',
            'MODELO' => 'LATITUDE',
            'DTAQUISICAO' => '2026-04-10',
            'DTBAIXA' => null,
            'PESO' => 2.5,
            'TAMANHO' => '14 POL',
            'VOLTAGEM' => '220V',
            'USUARIO' => $usuario->NMLOGIN,
            'DTOPERACAO' => now(),
            'UF' => 'SC',
        ], $overrides))->fresh();
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
