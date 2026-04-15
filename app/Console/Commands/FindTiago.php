<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FindTiago extends Command
{
    protected $signature = 'colabs:find-tiago';
    protected $description = 'Find all Tiago employees in database';

    public function handle()
    {
        $results = DB::table('funcionarios')
            ->select('CDMATRFUNCIONARIO', 'NMFUNCIONARIO')
            ->whereRaw('UPPER(NMFUNCIONARIO) LIKE ?', ['%TIAGO%'])
            ->limit(5)
            ->get();

        $this->info('Possíveis Tiagos encontrados:');
        foreach ($results as $row) {
            $this->line("  {$row->CDMATRFUNCIONARIO} | {$row->NMFUNCIONARIO}");
        }

        if ($results->isEmpty()) {
            $this->warn('Nenhum Tiago encontrado!');
        }
    }
}
