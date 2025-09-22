<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TipoPatrSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/DadosTipoPatr.TXT');
        if (!File::exists($path)) {
            $this->command->error("Arquivo de dados não encontrado: " . $path);
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Pula as duas linhas de cabeçalho
        $dataLines = array_slice($lines, 2);

        $data = [];
        foreach ($dataLines as $line) {
            // Extrai o código e a descrição baseando-se na estrutura de largura fixa
            // LINHA CORRIGIDA: Removido um ')' extra no final
            $id = (int)trim(substr($line, 0, 16));
            $descricao = trim(substr($line, 16));

            if ($id > 0) {
                $data[] = [
                    'NUSEQTIPOPATR' => $id,
                    'DETIPOPATR' => $descricao,
                ];
            }
        }

        // Deleta dados antigos para evitar duplicatas ao rodar o seeder novamente
        DB::table('TIPOPATR')->delete();
        // Insere os novos dados
        DB::table('TIPOPATR')->insert($data);
    }
}
