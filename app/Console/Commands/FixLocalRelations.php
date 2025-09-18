<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\LocalProjeto;

class FixLocalRelations extends Command
{
    protected $signature = 'app:fix-local-relations';
    protected $description = 'Corrige as chaves estrangeiras na tabela locais_projeto com base na regra de negócio final.';

    public function handle()
    {
        $this->info('Iniciando a correção final das relações...');

        // 1. Mapeia os projetos: CDFANTASIA/CDPROJETO => ID da tabela
        $projetosMap = DB::table('tabfant')->pluck('id', 'CDPROJETO');
        if ($projetosMap->isEmpty()) {
            $this->error('A tabela `tabfant` está vazia. Rode o TabfantSeeder primeiro.');
            return 1;
        }

        // 2. Pega todos os locais já importados do nosso banco
        $locais = LocalProjeto::all();
        if ($locais->isEmpty()) {
            $this->error('A tabela `locais_projeto` está vazia. Rode o LocaisProjetoSeeder primeiro.');
            return 1;
        }

        $updatedCount = 0;
        $bar = $this->output->createProgressBar($locais->count());
        $bar->start();

        // 3. Itera sobre os locais do NOSSO BANCO e os atualiza
        foreach ($locais as $local) {
            // A CHAVE DE LIGAÇÃO é o próprio ID do local, que corresponde ao NUSEQLOCALPROJ do arquivo original.
            $lookupKey = $local->id;

            // Busca no mapa de projetos usando essa chave
            $tabfantId = $projetosMap->get($lookupKey);

            if ($tabfantId) {
                $local->tabfant_id = $tabfantId;
                $local->save();
                $updatedCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Correção finalizada!");
        $this->info("{$updatedCount} registros atualizados com sucesso.");

        return 0;
    }
}
