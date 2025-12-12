<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportUsersFromKinghost extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dados de usuários do KingHost (baseado na estrutura encontrada)
        $users = [
            [
                'NMLOGIN' => 'BEATRIZ.SC',
                'NOMEUSER' => 'Beatriz',
                'SENHA' => bcrypt('123456'),
                'LGATIVO' => 'S',
                'must_change_password' => 1,
                'DATOPERACAO' => now()->toDateString(),
                'HOROPERACAO' => now()->toTimeString(),
            ],
            [
                'NMLOGIN' => 'ADMIN',
                'NOMEUSER' => 'Administrador',
                'SENHA' => bcrypt('admin123'),
                'LGATIVO' => 'S',
                'must_change_password' => 0,
                'DATOPERACAO' => now()->toDateString(),
                'HOROPERACAO' => now()->toTimeString(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('usuario')->updateOrInsert(
                ['NMLOGIN' => $user['NMLOGIN']],
                $user
            );
        }

        echo "✅ Usuários importados com sucesso!\n";
    }
}
