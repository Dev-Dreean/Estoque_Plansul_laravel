<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TabfantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Garante que a tabela esteja vazia antes de popular
        DB::table('tabfant')->truncate();

        $path = database_path('seeders/data/tabtansaia.TXT');
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Pula as linhas do cabeçalho
        array_splice($lines, 0, 2);

        $dataToInsert = [];
        foreach ($lines as $line) {
            // Extrai os dados baseado na posição dos caracteres (largura fixa)
            $cdFantasia = trim(substr($line, 0, 14));
            $deFantasia = trim(substr($line, 14, 61));
            $cdFilial = trim(substr($line, 75, 12));
            $ufProj = trim(substr($line, 87, 10));

            // Valida se a linha tem um código válido antes de adicionar
            if (is_numeric($cdFantasia)) {
                $dataToInsert[] = [
                    // Mapeia para as colunas da sua tabela 'tabfant'
                    // Assumindo que os nomes são CDPROJETO, NOMEPROJETO, LOCAL
                    // Se os nomes forem outros, ajuste aqui.
                    'CDPROJETO' => (int)$cdFantasia,
                    'NOMEPROJETO' => mb_convert_encoding($deFantasia, 'UTF-8', 'ISO-8859-1'),
                    'LOCAL' => is_numeric($cdFilial) ? (string)$cdFilial : null, // Ajuste conforme o tipo de dado da coluna
                    // 'UFPROJ' => ($ufProj === '<null>') ? null : $ufProj, // Se você tiver uma coluna para UF
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insere os dados em blocos para melhor performance
        foreach (array_chunk($dataToInsert, 200) as $chunk) {
            DB::table('tabfant')->insert($chunk);
        }

        $this->command->info('Tabela tabfant populada com sucesso!');
    }
}
