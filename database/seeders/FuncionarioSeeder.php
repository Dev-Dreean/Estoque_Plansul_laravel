<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Funcionario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FuncionarioSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/DadosFuncionarios.TXT');
        if (!File::exists($path)) {
            $this->command->error("Arquivo de dados de funcionários não encontrado: " . $path);
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $dataLines = array_slice($lines, 2);

        $this->command->info("Iniciando importação/atualização de funcionários...");
        $count = 0;

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

                // Converte a data para o formato do banco, se ela existir
                $dtAdmissao = null;
                if (!empty($dtAdmissaoStr)) {
                    $dtAdmissao = Carbon::createFromFormat('d/m/Y', $dtAdmissaoStr)->format('Y-m-d');
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
                Log::error("Erro ao importar funcionário na linha: {$line} - Erro: " . $e->getMessage());
            }
        }
        $this->command->info("{$count} registros de funcionários processados com sucesso!");
    }
}
