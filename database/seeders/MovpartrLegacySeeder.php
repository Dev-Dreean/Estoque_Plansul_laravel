<?php
// DENTRO DE database/seeders/MovpartrLegacySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovpartrLegacySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando importação dos dados legados para a tabela movpartr...');

        $caminhoArquivo = database_path('data/HistMovimentoPatr.TXT');
        if (!file_exists($caminhoArquivo)) {
            $this->command->error('Arquivo de dados não encontrado!');
            return;
        }

        $arquivo = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $novosRegistros = [];

        // Pula a linha do cabeçalho
        array_shift($arquivo);
        array_shift($arquivo);

        foreach ($arquivo as $linha) {
            // Lógica para extrair dados de largura fixa
            $nupatrim = trim(substr($linha, 0, 8));
            $nuproj = trim(substr($linha, 12, 6));
            $dtmovi = trim(substr($linha, 22, 10));
            $usuario = trim(substr($linha, 36, 35));

            if (empty($nupatrim)) continue; // Pula linhas vazias

            $novosRegistros[] = [
                'NUPATR' => $nupatrim,
                'CODPROJ' => $nuproj,
                'DTOPERACAO' => Carbon::createFromFormat('d/m/Y', $dtmovi)->toDateTimeString(),
                'USUARIO' => $usuario,
                'TIPO' => 'projeto',
                'CAMPO' => 'CDPROJETO',
                'VALOR_ANTIGO' => 'importado_sistema_antigo',
                'VALOR_NOVO' => $nuproj,
            ];
        }

        // Insere os dados em lotes
        foreach (array_chunk($novosRegistros, 500) as $chunk) {
            DB::table('movpartr')->insert($chunk);
        }

        $this->command->info('Importação concluída com sucesso!');
    }
}
