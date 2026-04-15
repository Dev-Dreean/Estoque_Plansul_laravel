<?php

namespace App\Console\Commands;

use App\Models\AcessoUsuario;
use App\Models\Funcionario;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProvisionSolicitanteTiUser extends Command
{
    protected $signature = 'solicitacoes:provisionar-solicitante-ti
        {matricula : Matrícula do colaborador}
        {login : Login que será usado no acesso}
        {--referencia=THEO : Login do usuário de referência para copiar as telas}
        {--perfil=C : Perfil do usuário (padrão: consultor)}
        {--reset-senha : Gera uma nova senha provisória mesmo se o usuário já existir}';

    protected $description = 'Cria ou atualiza o usuário solicitante da TI copiando as mesmas telas do usuário de referência.';

    public function handle(): int
    {
        $matricula = trim((string) $this->argument('matricula'));
        $login = trim((string) $this->argument('login'));
        $loginReferencia = trim((string) $this->option('referencia'));
        $perfil = trim((string) $this->option('perfil'));
        $resetSenha = (bool) $this->option('reset-senha');

        if ($matricula === '' || $login === '' || $loginReferencia === '') {
            $this->error('Informe matrícula, login e usuário de referência válidos.');

            return self::FAILURE;
        }

        $funcionario = Funcionario::query()
            ->where('CDMATRFUNCIONARIO', $matricula)
            ->first(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO']);

        if (!$funcionario) {
            $this->error("Funcionário {$matricula} não encontrado na tabela funcionarios.");

            return self::FAILURE;
        }

        $usuarioReferencia = User::query()
            ->whereRaw('UPPER(TRIM(NMLOGIN)) = ?', [mb_strtoupper($loginReferencia, 'UTF-8')])
            ->first();

        if (!$usuarioReferencia) {
            $this->error("Usuário de referência {$loginReferencia} não encontrado.");

            return self::FAILURE;
        }

        $telasReferencia = AcessoUsuario::query()
            ->where('CDMATRFUNCIONARIO', $usuarioReferencia->CDMATRFUNCIONARIO)
            ->whereRaw("TRIM(UPPER(INACESSO)) = 'S'")
            ->pluck('NUSEQTELA')
            ->map(fn ($tela) => (int) $tela)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($telasReferencia === []) {
            $this->error("O usuário de referência {$loginReferencia} não possui telas ativas para copiar.");

            return self::FAILURE;
        }

        $senhaProvisoria = null;
        $criadoAgora = false;

        DB::transaction(function () use (
            $matricula,
            $login,
            $perfil,
            $resetSenha,
            $funcionario,
            $telasReferencia,
            &$senhaProvisoria,
            &$criadoAgora
        ) {
            $usuario = User::query()
                ->where('CDMATRFUNCIONARIO', $matricula)
                ->orWhereRaw('UPPER(TRIM(NMLOGIN)) = ?', [mb_strtoupper($login, 'UTF-8')])
                ->first();

            if (!$usuario) {
                $usuario = new User();
                $criadoAgora = true;
            }

            $usuario->NOMEUSER = trim((string) $funcionario->NMFUNCIONARIO);
            $usuario->NMLOGIN = $login;
            $usuario->CDMATRFUNCIONARIO = $matricula;
            $usuario->PERFIL = $perfil !== '' ? $perfil : User::PERFIL_CONSULTOR;
            $usuario->LGATIVO = 'S';
            $usuario->needs_identity_update = false;

            if ($criadoAgora || $resetSenha) {
                $senhaProvisoria = 'Plansul@' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $usuario->SENHA = $senhaProvisoria;
                $usuario->must_change_password = true;
            } elseif ($usuario->must_change_password === null) {
                $usuario->must_change_password = false;
            }

            $usuario->save();

            AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $matricula)
                ->delete();

            foreach ($telasReferencia as $tela) {
                AcessoUsuario::create([
                    'CDMATRFUNCIONARIO' => $matricula,
                    'NUSEQTELA' => $tela,
                    'INACESSO' => 'S',
                ]);
            }
        });

        $this->info("Usuário {$login} provisionado com sucesso para a matrícula {$matricula}.");
        $this->line('Nome: ' . trim((string) $funcionario->NMFUNCIONARIO));
        $this->line('Telas copiadas: ' . implode(', ', $telasReferencia));

        if ($senhaProvisoria !== null) {
            $this->warn('Senha temporária gerada: ' . $senhaProvisoria);
            $this->line('O usuário será obrigado a trocar a senha no próximo acesso.');
        } else {
            $this->line('Senha mantida sem alterações.');
        }

        return self::SUCCESS;
    }
}
