<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Funcionario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FuncionarioSeeder extends Seeder
{
    public function run(): void
    {
        // 📋 Tentar primeiro o CSV (novo, completo)
        $csvPath = database_path('seeders/data/DadosFuncionarios.csv');
        $txtPath = database_path('seeders/data/DadosFuncionarios.TXT');

        if (File::exists($csvPath)) {
            $this->importFromCSV($csvPath);
        } elseif (File::exists($txtPath)) {
            $this->importFromTXT($txtPath);
        } else {
            $this->command->error("❌ Nenhum arquivo de funcionários encontrado (procurado: .csv e .TXT)");
            return;
        }
    }

    /**
     * Importa funcionários do CSV (novo formato - semicolon delimited)
     * 📝 CSV esperado com colunas: CDMATRFUNCIONARIO;NMFUNCIONARIO;DTADMISSAO;CDCARGO;CODFIL;UFPROJ
     */
    private function importFromCSV(string $path): void
    {
        $this->command->info("📊 Importando funcionários do CSV: {$path}");
        
        $file = fopen($path, 'r');
        if (!$file) {
            $this->command->error("❌ Não foi possível abrir o CSV: {$path}");
            return;
        }

        // Lê cabeçalho CSV
        $header = fgetcsv($file, 0, ';');
        if (!$header || !in_array('CDMATRFUNCIONARIO', $header)) {
            $this->command->error("❌ CSV inválido: cabeçalho esperado não encontrado");
            fclose($file);
            return;
        }

        $count = 0;
        $errorCount = 0;
        $batch = [];

        // Desabilita os checks de FK durante o import
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            while (($row = fgetcsv($file, 0, ';')) !== false) {
                try {
                    // Mapeia os valores do CSV
                    $matricula = trim($row[0] ?? '');
                    $nome = trim($row[1] ?? '');
                    $dtAdmissaoStr = trim($row[2] ?? '');
                    $cdCargo = trim($row[3] ?? '');
                    $codFilial = trim($row[4] ?? '');
                    $ufProj = trim($row[5] ?? '');

                    if (empty($matricula) || empty($nome)) {
                        continue;
                    }

                    // Converte data se existir
                    $dtAdmissao = null;
                    if (!empty($dtAdmissaoStr)) {
                        try {
                            $dtAdmissao = Carbon::createFromFormat('d/m/Y', $dtAdmissaoStr)->format('Y-m-d');
                        } catch (\Exception $e) {
                            Log::warning("⚠️  Data inválida para funcionário {$matricula}: {$dtAdmissaoStr}");
                        }
                    }

                    Funcionario::updateOrCreate(
                        ['CDMATRFUNCIONARIO' => $matricula],
                        [
                            'NMFUNCIONARIO' => $nome,
                            'DTADMISSAO' => $dtAdmissao,
                            'CDCARGO' => !empty($cdCargo) ? $cdCargo : null,
                            'CODFIL' => !empty($codFilial) ? $codFilial : null,
                            'UFPROJ' => !empty($ufProj) ? $ufProj : null,
                        ]
                    );
                    $count++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("❌ Erro ao importar funcionário: {$matricula} - " . $e->getMessage());
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            fclose($file);

            $this->command->info("✅ Importação concluída!");
            $this->command->line("   📊 Registros processados: {$count}");
            if ($errorCount > 0) {
                $this->command->warn("   ⚠️  Erros encontrados: {$errorCount}");
            }

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->command->error("❌ Erro geral: " . $e->getMessage());
        }
    }

    /**
     * Importa funcionários do TXT (formato legado - fixed width)
     * 📝 Mantido para compatibilidade retroativa
     */
    private function importFromTXT(string $path): void
    {
        $this->command->info("📄 Importando funcionários do TXT (legado): {$path}");

        $rawContent = File::get($path);
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $lines = explode(PHP_EOL, $utf8Content);
        $lines = array_filter($lines);
        $dataLines = array_slice($lines, 2);

        $count = 0;
        $errorCount = 0;

        foreach ($dataLines as $line) {
            try {
                $matricula = trim(substr($line, 0, 21));
                $nome = trim(substr($line, 21, 80));
                $dtAdmissaoStr = trim(substr($line, 82, 12));
                $cdCargo = trim(substr($line, 94, 52));
                $codFilial = trim(substr($line, 146, 10));
                $ufProj = trim(substr($line, 156, 10));

                if (empty($matricula) || empty($nome)) {
                    continue;
                }

                $dtAdmissao = null;
                if (!empty($dtAdmissaoStr)) {
                    try {
                        $dtAdmissao = Carbon::createFromFormat('d/m/Y', $dtAdmissaoStr)->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning("⚠️  Data inválida para funcionário {$matricula}: {$dtAdmissaoStr}");
                    }
                }

                Funcionario::updateOrCreate(
                    ['CDMATRFUNCIONARIO' => $matricula],
                    [
                        'NMFUNCIONARIO' => $nome,
                        'DTADMISSAO' => $dtAdmissao,
                        'CDCARGO' => $cdCargo,
                        'CODFIL' => $codFilial,
                        'UFPROJ' => $ufProj,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("❌ Erro ao importar funcionário TXT: " . $e->getMessage());
            }
        }

        $this->command->info("✅ Importação (TXT) concluída!");
        $this->command->line("   📊 Registros processados: {$count}");
        if ($errorCount > 0) {
            $this->command->warn("   ⚠️  Erros encontrados: {$errorCount}");
        }
    }
}
