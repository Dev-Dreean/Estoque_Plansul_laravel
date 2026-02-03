<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuscarTiposObjetos extends Command
{
    protected $signature = 'buscar:tipos-objetos';
    protected $description = 'Buscar tipos de objetos e dados do funcionário';

    public function handle()
    {
        $this->info("\n=== TIPOS DE OBJETOS ===\n");
        
        $tipos = DB::table('objetopatr')
            ->whereRaw("DEOBJETO LIKE '%GABINETE%' OR DEOBJETO LIKE '%CPU%' OR DEOBJETO LIKE '%ESCADA%' OR DEOBJETO LIKE '%APARADOR%' OR DEOBJETO LIKE '%NOTEBOOK%'")
            ->get(['NUSEQOBJETO', 'DEOBJETO']);

        foreach ($tipos as $tipo) {
            $this->line($tipo->NUSEQOBJETO . " | " . trim($tipo->DEOBJETO));
        }

        $this->info("\n=== BEATRIZ.SC ===\n");
        
        $beatriz = DB::table('funcionarios')
            ->whereRaw("NMFUNCIONARIO LIKE '%BEATRIZ%' OR CDMATRFUNCIONARIO LIKE 'BEATRIZ.SC%'")
            ->first(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO']);

        if ($beatriz) {
            $this->line("Matrícula: " . $beatriz->CDMATRFUNCIONARIO);
            $this->line("Nome: " . $beatriz->NMFUNCIONARIO);
        } else {
            $this->error("NÃO ENCONTRADO");
        }

        $this->info("\n=== LOCAIS PROJETO 940 ===\n");
        
        $locais = DB::table('locais_projeto')
            ->where('CDPROJETO', 940)
            ->get(['id', 'cdlocal', 'delocal']);

        foreach ($locais as $local) {
            $this->line($local->cdlocal . " | " . trim($local->delocal));
        }
    }
}
