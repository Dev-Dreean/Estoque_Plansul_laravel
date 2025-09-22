<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Adicione esta linha

class TabfantSeeder extends Seeder
{
    public function run(): void
    {
        // Desativa a checagem, limpa a tabela, e reativa.
        Schema::disableForeignKeyConstraints();
        DB::table('tabfant')->truncate();
        Schema::enableForeignKeyConstraints();

        $path = database_path('seeders/data/tabtansaia.TXT');
        if (!file_exists($path)) {
            $this->command->error('Arquivo de dados não encontrado: ' . $path);
            return;
        }
        // Conversão para UTF-8 do conteúdo inteiro
        $rawContent = file_get_contents($path);
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $lines = explode(PHP_EOL, $utf8Content);
        $lines = array_filter($lines);
        array_splice($lines, 0, 2); // remove cabeçalho

        $dataToInsert = [];
        foreach ($lines as $line) {
            $cdFantasia = trim(substr($line, 0, 14));
            $deFantasia = trim(substr($line, 14, 61));
            $cdFilial = trim(substr($line, 75, 12));

            if (is_numeric($cdFantasia)) {
                $dataToInsert[] = [
                    'CDPROJETO' => (int)$cdFantasia,
                    'NOMEPROJETO' => mb_convert_encoding($deFantasia, 'UTF-8', 'ISO-8859-1'),
                    'LOCAL' => is_numeric($cdFilial) ? (string)$cdFilial : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($dataToInsert, 200) as $chunk) {
            DB::table('tabfant')->insert($chunk);
        }

        $this->command->info('Tabela tabfant populada com sucesso!');
    }
}
