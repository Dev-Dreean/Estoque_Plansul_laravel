<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocaisProjetoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desativa a checagem, limpa a tabela, e reativa.
        Schema::disableForeignKeyConstraints();
        DB::table('locais_projeto')->truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Mapeia os projetos existentes: CDPROJETO => ID da tabela
        $projetosMap = DB::table('tabfant')->pluck('id', 'CDPROJETO');

        // 2. Lê o arquivo de locais
        $locaisPath = database_path('seeders/data/localProjeto.TXT');
        if (!file_exists($locaisPath)) {
            $this->command->error("Arquivo de dados não encontrado em: {$locaisPath}");
            return;
        }
        $locaisLines = file($locaisPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_splice($locaisLines, 0, 3); // Remove cabeçalho

        $fullLines = $this->parseIrregularLines($locaisLines);
        $dataToInsert = [];

        foreach ($fullLines as $line) {
            // Usa Expressão Regular para extrair os dados de forma robusta
            if (preg_match('/^\s*(\d+)\s+(\d+)\s+(.+?)\s+(\d+)\s+(.*)/', $line, $matches)) {

                $cdlocal = (int)$matches[2];
                $delocal = trim($matches[3]);
                $cdFantasiaDoLocal = (int)$matches[4];
                $flativoStr = trim($matches[5]);

                // Busca no mapa o ID do projeto correspondente usando a chave correta
                $tabfantId = $projetosMap->get($cdFantasiaDoLocal);

                $dataToInsert[] = [
                    'cdlocal' => $cdlocal,
                    'delocal' => mb_convert_encoding($delocal, 'UTF-8', 'ISO-8859-1'),
                    'flativo' => ($flativoStr !== '<null>' && is_numeric($flativoStr)) ? (bool)$flativoStr : false,
                    'tabfant_id' => $tabfantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insere os dados em blocos
        foreach (array_chunk($dataToInsert, 200) as $chunk) {
            DB::table('locais_projeto')->insert($chunk);
        }

        $this->command->info('Tabela locais_projeto populada e relacionada com sucesso!');
    }

    /**
     * Helper para tratar a quebra de linha irregular no arquivo de locais.
     */
    private function parseIrregularLines(array $lines): array
    {
        $fullLines = [];
        $tempLine = '';
        foreach ($lines as $line) {
            if (preg_match('/^\s*\d+/', substr($line, 0, 18))) {
                if (!empty($tempLine)) {
                    $fullLines[] = $tempLine;
                }
                $tempLine = $line;
            } else {
                $tempLine = rtrim($tempLine) . ' ' . trim($line);
            }
        }
        if (!empty($tempLine)) {
            $fullLines[] = $tempLine;
        }
        return $fullLines;
    }
}
