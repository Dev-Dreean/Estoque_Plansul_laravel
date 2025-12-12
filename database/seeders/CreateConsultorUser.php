<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CreateConsultorUser extends Seeder
{
    public function run()
    {
        User::firstOrCreate(
            ['NMLOGIN' => 'consultor'],
            [
                'NOMEUSER' => 'Consultor',
                'PASSWORD' => bcrypt('consultor123'),
                'PERFIL' => 'C',
                'CDMATRFUNCIONARIO' => 9999,
                'ATIVO' => 1,
            ]
        );

        $this->command->info('✅ Usuário consultor criado/verificado');
    }
}
