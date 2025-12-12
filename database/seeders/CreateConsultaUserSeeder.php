<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateConsultaUserSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuário de consulta padrão (read-only)
        User::updateOrCreate(
            ['NMLOGIN' => 'consulta'],
            [
                'NOMEUSER' => 'Usuário Consulta',
                'PERFIL' => 'USR',
                'SENHA' => Hash::make('consulta123'),
                'LGATIVO' => 'S',
                'role' => 'consulta',
            ]
        );

        echo "✅ Usuário 'consulta' criado com sucesso (senha: consulta123)\n";
    }
}
