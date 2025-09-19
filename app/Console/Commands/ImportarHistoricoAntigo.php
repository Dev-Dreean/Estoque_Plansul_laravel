<?php
// DENTRO DE app/Console/Commands/ImportarHistoricoAntigo.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportarHistoricoAntigo extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'historico:importar';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Importa o histórico de movimentações da tabela legada MOVPATR para a nova tabela movpartr.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando a importação do histórico antigo...');

        if (!\Illuminate\Support\Facades\Schema::hasTable('MOVPATR')) {
            $this->error('A tabela legada MOVPATR não foi encontrada no banco de dados.');
            return 1;
        }

        // Usamos uma transação para garantir que tudo seja inserido ou nada.
        DB::transaction(function () {
            $totalRegistros = DB::table('MOVPATR')->count();
            if ($totalRegistros === 0) {
                $this->warn('A tabela MOVPATR está vazia. Nada a importar.');
                return;
            }

            $progressBar = $this->output->createProgressBar($totalRegistros);
            $progressBar->start();

            // Usamos chunkById para processar em lotes e não sobrecarregar a memória
            DB::table('MOVPATR')->orderBy('NUPATRIM')->chunk(200, function ($registrosAntigos) use ($progressBar) {
                $novosRegistros = [];
                foreach ($registrosAntigos as $registro) {
                    $novosRegistros[] = [
                        'NUPATR' => $registro->NUPATRIM,
                        'CODPROJ' => $registro->NUPROJ,
                        'DTOPERACAO' => $registro->DTMOVI, // Usando o timestamp
                        'USUARIO' => $registro->USUARIO,
                        'TIPO' => 'projeto', // Assumindo que toda movimentação antiga era de projeto
                        'CAMPO' => 'CDPROJETO',
                        'VALOR_ANTIGO' => 'desconhecido', // Não temos essa informação na tabela antiga
                        'VALOR_NOVO' => $registro->NUPROJ,
                        'CO_AUTOR' => null, // Não temos essa informação
                    ];
                }

                // Inserção em massa para melhor performance
                DB::table('movpartr')->insert($novosRegistros);
                $progressBar->advance(count($novosRegistros));
            });

            $progressBar->finish();
        });

        $this->info("\nImportação concluída com sucesso!");
        return 0;
    }
}
