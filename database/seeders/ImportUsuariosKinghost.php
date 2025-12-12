<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportUsuariosKinghost extends Seeder
{
    public function run(): void
    {
        // Dados dos usuários do KingHost (mapeados para estrutura local)
        $usuarios = [
            [
                'CDMATRFUNCIONARIO' => '99999999',  // Username (NMLOGIN no KingHost)
                'NMFUNCIONARIO' => 'André Oliveira',
                'email' => 'aoliveira@example.com',
                'password' => '$2y$12$vVguo.kCdv3l9ZKf2TR4k./wPuHB5Rl7u4gevfa9Pky/AJ4ThWeni', // Já hashado
            ],
            [
                'CDMATRFUNCIONARIO' => '182687',
                'NMFUNCIONARIO' => 'BEATRIZ PATRICIA VIRISSIMO DOS SANTOS',
                'email' => 'beatriz@example.com',
                'password' => '$2y$12$nlx71ySxEovBn8vb60AXtO/Ar4G75frh.XoeveGNqkjn5EigcuCJS',
            ],
            [
                'CDMATRFUNCIONARIO' => '00',
                'NMFUNCIONARIO' => 'BRUNO',
                'email' => 'bruno@example.com',
                'password' => '$2y$12$jreEWKuiLil4DmhJCH2SmOc7niek54gImov3zgGNX.N.rE8NckBxK',
            ],
        ];

        foreach ($usuarios as $usuario) {
            DB::table('usuario')->updateOrInsert(
                ['CDMATRFUNCIONARIO' => $usuario['CDMATRFUNCIONARIO']],
                $usuario
            );
        }

        echo "✅ Usuários importados com sucesso!\n";
    }
}

