<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\ObjetoPatr;

class ObjetoPatrSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/DadosObjetoPatr.TXT');
        if (!File::exists($path)) {
            $this->command->error("Arquivo de dados não encontrado: " . $path);
            return;
        }

        // Conversão para UTF-8 evitando problemas de caracteres
        $rawContent = File::get($path);
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $lines = explode(PHP_EOL, $utf8Content);
        $lines = array_filter($lines);
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

        // Deleta dados antigos para evitar duplicatas (usa o Model para respeitar case da tabela)
        ObjetoPatr::query()->delete();
        // Insere os novos dados
        ObjetoPatr::query()->insert($data);
    }
}
