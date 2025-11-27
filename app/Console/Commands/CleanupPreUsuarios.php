<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CleanupPreUsuarios extends Command
{
    protected $signature = 'cleanup:pre-usuarios';
    protected $description = 'Remove o sufixo (PRE) dos nomes dos usuários pré-cadastrados';

    public function handle()
    {
        $this->info("=== LIMPANDO SUFIXO (PRE) DOS USUÁRIOS ===\n");

        // Buscar todos os usuários com "(PRE)" no nome
        $usuarios = User::where('NOMEUSER', 'like', '%PRE%')->get();

        $this->info("Usuários encontrados com (PRE): " . count($usuarios) . "\n");

        foreach ($usuarios as $user) {
            $nomeNovo = trim(str_replace('(PRE)', '', $user->NOMEUSER));
            $nomAntigo = $user->NOMEUSER;
            
            $user->update(['NOMEUSER' => $nomeNovo]);
            
            $this->line("✓ {$nomAntigo} → {$nomeNovo}");
        }

        $this->info("\n✓ Limpeza concluída!");
        return 0;
    }
}
