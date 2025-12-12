<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateConsultorUser extends Command
{
    protected $signature = 'user:create-consultor';
    protected $description = 'Cria um usuário consultor para testes';

    public function handle()
    {
        $user = User::where('NMLOGIN', 'consultor')->first();

        if ($user) {
            $this->info("✅ Usuário 'consultor' já existe!");
            return;
        }

        $user = User::create([
            'NOMEUSER' => 'Usuário Consultor',
            'NMLOGIN' => 'consultor',
            'PERFIL' => 'C',
            'SENHA' => Hash::make('consultor123'),
            'LGATIVO' => 'S',
            'CDMATRFUNCIONARIO' => '999999',
        ]);

        $this->info("✅ Usuário 'consultor' criado com sucesso! (senha: consultor123)");
    }
}
