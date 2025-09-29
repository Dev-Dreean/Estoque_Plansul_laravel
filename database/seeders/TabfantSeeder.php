<?php

// Caminho: database/seeders/TabfantSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TabfantSeeder extends Seeder
{
    public function run()
    {
        // Usar truncate é mais eficiente para limpar a tabela.
        DB::table('tabfant')->truncate();

        $path = database_path('seeders/data/tabtansaia.TXT');
        if (!file_exists($path)) {
            $this->command->error("Arquivo de dados não encontrado: " . $path);
            return;
        }

        $file = fopen($path, 'r');
        if (!$file) {
            $this->command->error("Não foi possível abrir o arquivo: " . $path);
            return;
        }

        $projetos = [];
        $now = now();
        fgets($file); // Ignora cabeçalho
        fgets($file); // Ignora linha de separadores

        while (($line = fgets($file)) !== false) {
            $encoding = mb_detect_encoding($line, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding !== 'UTF-8') {
                $line = mb_convert_encoding($line, 'UTF-8', $encoding);
            }

            $cdfantasia = trim(substr($line, 0, 14));
            $defantasia = trim(substr($line, 14, 61));
            $ufproj = trim(substr($line, 87, 10));

            $id = is_numeric($cdfantasia) ? (int)$cdfantasia : null;
            if ($id === null || empty($defantasia)) {
                continue;
            }

            $projetos[] = [
                'id'          => $id, // ID explícito para manter a referência do arquivo original
                'NOMEPROJETO' => $defantasia,
                'CDPROJETO'   => $cdfantasia, // Este é o código que usamos como chave
                'LOCAL'       => $ufproj,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        fclose($file);

        if (!empty($projetos)) {
            foreach (array_chunk($projetos, 500) as $chunk) {
                DB::table('tabfant')->insert($chunk);
            }
        }
        $this->command->info('Tabela tabfant populada com sucesso!');
    }
}
