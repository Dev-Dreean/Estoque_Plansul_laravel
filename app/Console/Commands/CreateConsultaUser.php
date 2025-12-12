<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateConsultaUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-consulta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criar um usuário de consulta (leitura) simples';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verificar se usuário já existe
        $existing = User::where('NMLOGIN', 'consulta')->first();
        if ($existing) {
            $this->info("✅ Usuário 'consulta' já existe (NUSEQUSUARIO={$existing->NUSEQUSUARIO})");
            return 0;
        }

        // Criar usuário consulta
        $usuario = User::create([
            'NOMEUSER' => 'Usuário Consulta',
            'NMLOGIN' => 'consulta',
            'PERFIL' => 'USR',  // Usuário comum, sem direito de admin
            'SENHA' => Hash::make('consulta123'),
            'LGATIVO' => 'S',
            'CDMATRFUNCIONARIO' => '999999',  // Dummy
        ]);

        $this->info("✅ Usuário 'consulta' criado com sucesso!");
        $this->line("  Login: consulta");
        $this->line("  Senha: consulta123");
        $this->line("  Perfil: USR (Usuário comum - consulta/leitura)");
        $this->line("  NUSEQUSUARIO: {$usuario->NUSEQUSUARIO}");

        return 0;
    }
}
