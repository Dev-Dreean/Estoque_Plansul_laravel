<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TipoPatr;
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

        // Limpa a tabela antes de começar (usa Model para respeitar o case da tabela)
        TipoPatr::query()->delete();

        // Conversão para UTF-8 evitando problemas de caracteres especiais
        $rawContent = File::get($path);
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $lines = explode(PHP_EOL, $utf8Content);
        $lines = array_filter($lines);
        $dataLines = array_slice($lines, 2);

        $this->command->info('Iniciando importação de TIPOPATR (um por um)...');
        $successCount = 0;
        $errorCount = 0;

        foreach ($dataLines as $index => $line) {
            try {
                $id = (int)trim(substr($line, 0, 16));
                $descricao = trim(substr($line, 16));

                if ($id > 0) {
                    TipoPatr::create([
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
