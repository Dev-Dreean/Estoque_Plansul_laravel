<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar se já existe
        $existing = User::where('NMLOGIN', 'lucas')->first();
        if ($existing) {
            echo "Usuário 'lucas' já existe.\n";
            return;
        }

        // Criar usuário de teste
        $user = new User();
        $user->NOMEUSER = 'Lucas Teste';
        $user->NMLOGIN = 'lucas';
        $user->SENHA = '1234'; // O mutator já criptografa automaticamente
        $user->PERFIL = 'ADM';
        $user->LGATIVO = 'S';
        $user->CDMATRFUNCIONARIO = 1;
        $user->save();

        echo "Usuário de teste criado:\n";
        echo "Login: lucas\n";
        echo "Senha: 1234\n";
        echo "Perfil: ADM\n";
    }
}
