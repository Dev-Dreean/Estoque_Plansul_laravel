<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ConfigurarSupervisor extends Command
{
    protected $signature = 'supervisor:configurar {login} {--todos : Supervisionar todos os usuários}';
    protected $description = 'Configura um usuário como supervisor';

    public function handle()
    {
        $login = $this->argument('login');
        $todos = $this->option('todos');

        $usuario = User::where('NMLOGIN', $login)->first();

        if (!$usuario) {
            $this->error("Usuário {$login} não encontrado!");
            return 1;
        }

        if ($todos) {
            // Buscar todos os logins EXCETO o próprio e admins
            $todosLogins = User::where('NMLOGIN', '!=', $login)
                ->where('PERFIL', 'USR')
                ->pluck('NMLOGIN')
                ->toArray();

            $usuario->supervisor_de = $todosLogins;
            $usuario->save();

            $this->info("✓ {$usuario->NOMEUSER} ({$login}) agora supervisiona " . count($todosLogins) . " usuários:");
            foreach ($todosLogins as $l) {
                $this->line("  - {$l}");
            }
        } else {
            // Modo interativo - escolher quem supervisionar
            $disponiveis = User::where('NMLOGIN', '!=', $login)
                ->where('PERFIL', 'USR')
                ->get(['NMLOGIN', 'NOMEUSER']);

            $this->info("Usuários disponíveis para supervisionar:");
            foreach ($disponiveis as $idx => $u) {
                $this->line("  [{$idx}] {$u->NOMEUSER} ({$u->NMLOGIN})");
            }

            $escolhidos = $this->ask('Digite os números separados por vírgula (ex: 0,1,2) ou "todos"');

            if (strtolower($escolhidos) === 'todos') {
                $usuario->supervisor_de = $disponiveis->pluck('NMLOGIN')->toArray();
            } else {
                $indices = array_map('trim', explode(',', $escolhidos));
                $loginsEscolhidos = [];
                foreach ($indices as $idx) {
                    if (isset($disponiveis[$idx])) {
                        $loginsEscolhidos[] = $disponiveis[$idx]->NMLOGIN;
                    }
                }
                $usuario->supervisor_de = $loginsEscolhidos;
            }

            $usuario->save();
            $this->info("✓ {$usuario->NOMEUSER} ({$login}) agora supervisiona " . count($usuario->supervisor_de) . " usuários");
        }

        return 0;
    }
}
