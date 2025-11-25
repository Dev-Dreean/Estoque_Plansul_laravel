<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestaurarTelasPrincipais extends Command
{
    protected $signature = 'telas:restaurar-principais';
    protected $description = 'Garante que as telas principais existam e estejam ativas (FLACESSO = S)';

    public function handle()
    {
        $telas = [
            ['codigo' => 1000, 'nome' => 'Controle de Patrimônio', 'nmsistema' => 'Plansul'],
            ['codigo' => 1001, 'nome' => 'Dashboard - Gráficos', 'nmsistema' => 'Plansul'],
            ['codigo' => 1002, 'nome' => 'Cadastro de Locais', 'nmsistema' => 'Plansul'],
            ['codigo' => 1003, 'nome' => 'Cadastro de Usuários', 'nmsistema' => 'Plansul'],
            ['codigo' => 1004, 'nome' => 'Cadastro de Telas', 'nmsistema' => 'Plansul'],
            ['codigo' => 1005, 'nome' => 'Gerenciar Acessos', 'nmsistema' => 'Plansul'],
            ['codigo' => 1006, 'nome' => 'Relatórios', 'nmsistema' => 'Plansul'],
            ['codigo' => 1007, 'nome' => 'Histórico de Movimentações', 'nmsistema' => 'Plansul'],
            ['codigo' => 1008, 'nome' => 'Configurações de Tema', 'nmsistema' => 'Plansul'],
        ];

        $inserted = 0;
        $updated = 0;

        foreach ($telas as $t) {
            $exists = DB::table('acessotela')->where('NUSEQTELA', $t['codigo'])->first();
            if ($exists) {
                DB::table('acessotela')->where('NUSEQTELA', $t['codigo'])->update([
                    'DETELA' => $t['nome'],
                    'NMSISTEMA' => $t['nmsistema'],
                    'FLACESSO' => 'S',
                    'NIVEL_VISIBILIDADE' => $exists->NIVEL_VISIBILIDADE ?? 'TODOS',
                ]);
                $updated++;
            } else {
                DB::table('acessotela')->insert([
                    'NUSEQTELA' => $t['codigo'],
                    'DETELA' => $t['nome'],
                    'NMSISTEMA' => $t['nmsistema'],
                    'FLACESSO' => 'S',
                    'NIVEL_VISIBILIDADE' => 'TODOS',
                ]);
                $inserted++;
            }
        }

        $this->info("Telas principais atualizadas. Inseridas: $inserted, Atualizadas: $updated");
        return 0;
    }
}
