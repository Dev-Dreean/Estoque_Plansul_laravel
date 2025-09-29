<?php

// Caminho: app/Console/Commands/FixLocalRelations.php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLocalRelations extends Command
{
    /**
     * O nome e a assinatura do comando do console.
     * --report-only: Apenas lista os problemas, não altera o banco.
     * --force: Executa as correções no banco de dados.
     */
    protected $signature = 'app:fix-local-relations {--report-only} {--force}';

    /**
     * A descrição do comando do console.
     */
    protected $description = 'Verifica e corrige relacionamentos quebrados entre LocalProjeto e Tabfant.';

    public function handle(): int
    {
        $reportOnly = $this->option('report-only');
        $force = $this->option('force');

        if (!$reportOnly && !$force) {
            $this->warn('Nenhuma ação será executada. Use --report-only para ver os problemas ou --force para corrigi-los.');
            return self::SUCCESS;
        }

        $title = $reportOnly ? 'Relatório de Diagnóstico' : 'Tentativa de Correção';
        $this->info("=============================================");
        $this->info("== {$title} de Relacionamentos ==");
        $this->info("=============================================");

        // Pega todos os IDs válidos da tabela de projetos (tabfant)
        $validProjectIds = Tabfant::pluck('id')->all();

        // Encontra todos os locais que têm um tabfant_id não nulo, mas que não existe na tabela de projetos
        $brokenLocais = LocalProjeto::whereNotNull('tabfant_id')
            ->whereNotIn('tabfant_id', $validProjectIds)
            ->get();

        if ($brokenLocais->isEmpty()) {
            $this->info('Nenhum relacionamento quebrado encontrado. O banco de dados parece consistente!');
            return self::SUCCESS;
        }

        $this->warn("Encontrados {$brokenLocais->count()} locais com Projeto Associado inválido:");
        $headers = ['ID Local', 'Cód. Local', 'Nome do Local', 'ID Projeto Inválido', 'Status'];
        $rows = [];
        $fixedCount = 0;

        foreach ($brokenLocais as $local) {
            $status = 'Inválido. Nenhuma correspondência encontrada.';

            // Tenta encontrar um projeto correspondente pela lógica alternativa
            $projetoAlternativo = Tabfant::where('CDPROJETO', (string) $local->cdlocal)->first();

            if ($projetoAlternativo) {
                $status = "CORRIGÍVEL -> Projeto '{$projetoAlternativo->NOMEPROJETO}' (ID: {$projetoAlternativo->id})";

                if ($force) {
                    $local->tabfant_id = $projetoAlternativo->id;
                    $local->save();
                    $fixedCount++;
                    $status = "CORRIGIDO -> Associado ao projeto '{$projetoAlternativo->NOMEPROJETO}'";
                }
            }

            $rows[] = [
                $local->id,
                $local->cdlocal,
                $local->delocal,
                $local->tabfant_id,
                $status,
            ];
        }

        $this->table($headers, $rows);

        if ($force) {
            $this->info("=============================================");
            $this->info("Correção concluída. {$fixedCount} de {$brokenLocais->count()} locais foram atualizados.");
        } else {
            $this->warn("\nExecute o comando com a flag --force para aplicar as correções sugeridas.");
        }

        return self::SUCCESS;
    }
}
