<?php

// Caminho: database/seeders/LocaisProjetoSeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocaisProjetoSeeder extends Seeder
{
    /**
     * Popula a tabela locais_projeto com uma lógica de parsing robusta.
     */
    public function run(): void
    {
        DB::table('locais_projeto')->truncate();
        $this->command->info('Tabela locais_projeto limpa.');

        $path = database_path('seeders/data/localProjeto.TXT');
        if (!file_exists($path)) {
            $this->command->error("Arquivo de dados não encontrado: {$path}");
            return;
        }

        $projetosMap = DB::table('tabfant')->pluck('id', 'CDPROJETO')->all();
        $countProjetos = count($projetosMap);
        $this->command->info("{$countProjetos} projetos mapeados para associação.");

        $file = fopen($path, 'r');
        if (!$file) {
            $this->command->error("Não foi possível abrir o arquivo: {$path}");
            return;
        }

        $locaisParaInserir = [];
        $now = Carbon::now();
        $count = 0;
        $associationsMade = 0;
        $notFoundCount = 0;

        fgets($file); // Pula cabeçalho
        fgets($file); // Pula linha de separação

        while (($line = fgets($file)) !== false) {
            // Garante a consistência da codificação.
            $encoding = mb_detect_encoding($line, ['UTF-8', 'ISO-8859-1'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $line = mb_convert_encoding($line, 'UTF-8', $encoding);
            }

            // ==================================================================
            // INÍCIO DA CORREÇÃO FINAL: Usando preg_split para extrair colunas.
            // Isso é robusto e não depende de posições fixas de caracteres.
            // ==================================================================
            $columns = preg_split('/\s{2,}/', trim($line)); // Divide a linha por 2 ou mais espaços

            if (count($columns) < 5) { // Validação mínima para a linha ter dados
                continue;
            }

            // A estrutura esperada após o split é:
            // [0] => NUSEQLOCALPROJ, [1] => CDLOCAL, [2] => DELOCAL, [3] => CDFANTASIA, [4] => FLATIVO
            $cdlocal = $columns[1];
            $delocal = $columns[2];
            $cdfantasiaDoLocal = $columns[3];
            $flativoStr = $columns[4];
            // ==================================================================
            // FIM DA CORREÇÃO
            // ==================================================================

            if (empty($cdlocal) || empty($delocal)) {
                continue;
            }

            $tabfant_id = null;
            if ($cdfantasiaDoLocal && $cdfantasiaDoLocal !== '<null>' && isset($projetosMap[$cdfantasiaDoLocal])) {
                $tabfant_id = $projetosMap[$cdfantasiaDoLocal];
                $associationsMade++;
            } else {
                if ($cdfantasiaDoLocal && $cdfantasiaDoLocal !== '<null>') {
                    $notFoundCount++;
                }
            }

            $locaisParaInserir[] = [
                'cdlocal'    => (int)$cdlocal,
                'delocal'    => $delocal,
                'tabfant_id' => $tabfant_id,
                'flativo'    => ($flativoStr !== '<null>' && $flativoStr !== '') ? (bool)$flativoStr : true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $count++;
        }
        fclose($file);

        if (!empty($locaisParaInserir)) {
            foreach (array_chunk($locaisParaInserir, 500) as $chunk) {
                DB::table('locais_projeto')->insert($chunk);
            }
        }

        $this->command->info("Seeder concluído. {$count} locais processados.");
        $this->command->info("{$associationsMade} associações realizadas com sucesso!");
        if ($notFoundCount > 0) {
            $this->command->error("{$notFoundCount} locais tinham um CDFANTASIA mas não encontraram projeto correspondente.");
        }
    }
}
