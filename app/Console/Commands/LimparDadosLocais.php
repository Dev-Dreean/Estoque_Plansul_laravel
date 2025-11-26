<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimparDadosLocais extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locais:limpar {--dry-run : Apenas mostra o que será deletado sem deletar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa dados corrompidos da tabela locais_projeto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Limpeza de Dados Corrompidos - Locais Projeto ===');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        // 1. Verificar registros com cdlocal inválido (0 ou 1)
        $corrompidos = DB::table('locais_projeto')
            ->where(function ($query) {
                $query->where('cdlocal', '0')
                      ->orWhere('cdlocal', '1');
            })
            ->select('id', 'cdlocal', 'delocal', 'tabfant_id')
            ->get();

        $this->info("Total de registros corrompidos encontrados: " . $corrompidos->count());
        $this->newLine();

        if ($corrompidos->count() > 0) {
            $this->table(
                ['ID', 'CdLocal', 'Nome do Local', 'Tabfant ID'],
                $corrompidos->take(20)->map(function ($local) {
                    return [
                        $local->id,
                        $local->cdlocal,
                        substr($local->delocal ?? 'NULL', 0, 40),
                        $local->tabfant_id ?? 'NULL'
                    ];
                })->toArray()
            );

            if ($dryRun) {
                $this->warn('Modo DRY-RUN: Nenhuma alteração será feita.');
                $this->info("Seriam deletados {$corrompidos->count()} registros.");
            } else {
                if ($this->confirm('Deseja deletar esses registros corrompidos?', true)) {
                    $deletados = DB::table('locais_projeto')
                        ->where(function ($query) {
                            $query->where('cdlocal', '0')
                                  ->orWhere('cdlocal', '1');
                        })
                        ->delete();

                    $this->info("✓ Total de registros deletados: $deletados");
                } else {
                    $this->warn('Operação cancelada.');
                }
            }
        } else {
            $this->info('✓ Nenhum dado corrompido encontrado!');
        }

        $this->newLine();

        // 2. Verificar registros sem projeto
        $semProjeto = DB::table('locais_projeto')
            ->whereNull('tabfant_id')
            ->count();

        $this->info("Registros sem projeto vinculado: $semProjeto");

        // 3. Verificar duplicados
        $duplicados = DB::table('locais_projeto')
            ->select('cdlocal', DB::raw('COUNT(*) as total'))
            ->groupBy('cdlocal')
            ->having('total', '>', 1)
            ->get();

        if ($duplicados->count() > 0) {
            $this->warn("Códigos de local duplicados encontrados: " . $duplicados->count());
        }

        $this->newLine();
        $this->info('=== Fim da Análise ===');

        return 0;
    }
}

