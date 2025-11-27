<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GerenciarSupervisao extends Command
{
    protected $signature = 'supervisor:gerenciar {acao} {login?}';
    protected $description = 'Gerencia supervisão de usuários (listar, remover)';

    public function handle()
    {
        $acao = $this->argument('acao');
        $login = $this->argument('login');

        switch ($acao) {
            case 'listar':
                return $this->listar();
            case 'remover':
                return $this->remover($login);
            default:
                $this->error("Ação inválida! Use: listar ou remover");
                return 1;
        }
    }

    private function listar()
    {
        $supervisores = User::whereNotNull('supervisor_de')->get();

        if ($supervisores->isEmpty()) {
            $this->info("Nenhum supervisor configurado.");
            return 0;
        }

        $this->info("=== SUPERVISORES CONFIGURADOS ===\n");
        foreach ($supervisores as $sup) {
            $supervisionados = $sup->supervisor_de ?? [];
            $this->line("👤 {$sup->NOMEUSER} ({$sup->NMLOGIN})");
            $this->line("   Supervisiona " . count($supervisionados) . " usuários:");
            foreach ($supervisionados as $login) {
                $user = User::where('NMLOGIN', $login)->first(['NOMEUSER']);
                $nome = $user ? $user->NOMEUSER : $login;
                $this->line("     - {$nome} ({$login})");
            }
            $this->line("");
        }

        return 0;
    }

    private function remover($login)
    {
        if (!$login) {
            $this->error("Você deve informar o login do supervisor!");
            return 1;
        }

        $usuario = User::where('NMLOGIN', $login)->first();

        if (!$usuario) {
            $this->error("Usuário {$login} não encontrado!");
            return 1;
        }

        $usuario->supervisor_de = null;
        $usuario->save();

        $this->info("✓ Supervisão removida de {$usuario->NOMEUSER} ({$login})");
        return 0;
    }
}
