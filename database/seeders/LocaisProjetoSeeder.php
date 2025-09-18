<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocaisProjetoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('locais_projeto')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $projetosMap = DB::table('tabfant')->pluck('id', 'CDPROJETO');

        $locaisPath = database_path('seeders/data/localProjeto.TXT');
        $locaisLines = file($locaisPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_splice($locaisLines, 0, 3);

        $fullLines = $this->parseIrregularLines($locaisLines);
        $dataToInsert = [];

        foreach ($fullLines as $line) {
            $cdFantasiaDoLocal = (int)trim(substr($line, 129, 13));
            $tabfantForeignKey = $projetosMap->get($cdFantasiaDoLocal);

            $dataToInsert[] = [
                'cdlocal' => (int)trim(substr($line, 18, 11)),
                // ===== CORREÇÃO ESTÁ NESTA LINHA =====
                'delocal' => mb_convert_encoding(trim(substr($line, 29, 100)), 'UTF-8', 'ISO-8859-1'),
                'flativo' => (bool)trim(substr($line, 142, 10)),
                'tabfant_id' => $tabfantForeignKey,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($dataToInsert, 200) as $chunk) {
            DB::table('locais_projeto')->insert($chunk);
        }

        $this->command->info('Tabela locais_projeto populada e relacionada com sucesso!');
    }

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
