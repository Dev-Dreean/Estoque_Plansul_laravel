<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ObjetoPatrSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/DadosObjetoPatr.TXT');
        if (!File::exists($path)) {
            $this->command->error("Arquivo de dados não encontrado: " . $path);
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $dataLines = array_slice($lines, 2);

        $data = [];
        foreach ($dataLines as $line) {
            // LINHAS CORRIGIDAS: Removido um ')' extra no final de cada linha
            $idObjeto = (int)trim(substr($line, 0, 14));
            $idTipo = (int)trim(substr($line, 14, 17));
            $descricao = trim(substr($line, 31));

            if ($idObjeto > 0) {
                $data[] = [
                    'NUSEQOBJETO' => $idObjeto,
                    'NUSEQTIPOPATR' => $idTipo,
                    'DEOBJETO' => $descricao,
                ];
            }
        }

        // Deleta dados antigos para evitar duplicatas
        DB::table('OBJETOPATR')->delete();
        // Insere os novos dados
        DB::table('OBJETOPATR')->insert($data);
    }
}
