<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TipoPatrSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/DadosTipoPatr.TXT');
        if (!File::exists($path)) {
            $this->command->error("Arquivo de dados não encontrado: " . $path);
            return;
        }

        // Limpa a tabela antes de começar
        DB::table('TIPOPATR')->delete();

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $dataLines = array_slice($lines, 2);

        $this->command->info('Iniciando importação de TIPOPATR (um por um)...');
        $successCount = 0;
        $errorCount = 0;

        foreach ($dataLines as $index => $line) {
            try {
                $id = (int)trim(substr($line, 0, 16));
                $descricao = trim(substr($line, 16));

                if ($id > 0) {
                    DB::table('TIPOPATR')->insert([
                        'NUSEQTIPOPATR' => $id,
                        'DETIPOPATR' => $descricao,
                    ]);
                    $successCount++;
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // Se der erro, informa qual linha e qual o erro
                $lineNumber = $index + 3; // +2 do slice, +1 do array index
                $this->command->error("Erro ao inserir a linha #{$lineNumber} do arquivo .TXT: " . $e->getMessage());
                Log::error("TipoPatrSeeder Falha na linha {$lineNumber}: {$line}", ['exception' => $e]);
                $errorCount++;
            }
        }

        $this->command->info("Importação de TIPOPATR concluída. Sucesso: {$successCount}, Erros: {$errorCount}.");
    }
}
