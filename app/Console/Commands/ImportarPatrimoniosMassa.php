<?php

namespace App\Console\Commands;

use App\Models\Patrimonio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarPatrimoniosMassa extends Command
{
    protected $signature = 'importar:patrimonios-massa';
    protected $description = 'Importar patrimônios em massa';

    public function handle()
    {
        $dados = [
            // GABINETE - 3 unidades
            ['nupatrimonio' => 14956, 'oc' => 56426, 'desc' => 'GABINETE', 'obs' => 'ESTOQUE STI', 'projeto' => 8, 'local' => 1969, 'marca' => 'TGT', 'modelo' => 'COM FONTE 200W', 'situacao' => 'A DISPOSIÇÃO', 'dtaq' => '2026-01-30', 'codobjeto' => 280],
            ['nupatrimonio' => 14957, 'oc' => 56426, 'desc' => 'GABINETE', 'obs' => 'ESTOQUE STI', 'projeto' => 8, 'local' => 1969, 'marca' => 'TGT', 'modelo' => 'COM FONTE 200W', 'situacao' => 'A DISPOSIÇÃO', 'dtaq' => '2026-01-30', 'codobjeto' => 280],
            ['nupatrimonio' => 14958, 'oc' => 56426, 'desc' => 'GABINETE', 'obs' => 'ESTOQUE STI', 'projeto' => 8, 'local' => 1969, 'marca' => 'TGT', 'modelo' => 'COM FONTE 200W', 'situacao' => 'A DISPOSIÇÃO', 'dtaq' => '2026-01-30', 'codobjeto' => 280],
            
            // CPU - 3 unidades
            ['nupatrimonio' => 14963, 'oc' => 56279, 'desc' => 'CPU', 'obs' => 'ESTOQUE STI', 'projeto' => 8, 'local' => 1969, 'marca' => '', 'modelo' => '16GB RAM', 'situacao' => 'A DISPOSIÇÃO', 'dtaq' => '2026-01-21', 'codobjeto' => 151],
            ['nupatrimonio' => 14964, 'oc' => 56279, 'desc' => 'CPU', 'obs' => 'ESTOQUE STI', 'projeto' => 8, 'local' => 1969, 'marca' => '', 'modelo' => '16GB RAM', 'situacao' => 'A DISPOSIÇÃO', 'dtaq' => '2026-01-21', 'codobjeto' => 151],
            ['nupatrimonio' => 14965, 'oc' => 56279, 'desc' => 'CPU', 'obs' => 'ESTOQUE STI', 'projeto' => 8, 'local' => 1969, 'marca' => '', 'modelo' => '16GB RAM', 'situacao' => 'A DISPOSIÇÃO', 'dtaq' => '2026-01-21', 'codobjeto' => 151],
            
            // ESCADA - 5 unidades
            ['nupatrimonio' => 14951, 'oc' => 56172, 'desc' => 'ESCADA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => '', 'modelo' => '3,8M 13 DEGRAUS', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 239],
            ['nupatrimonio' => 14952, 'oc' => 56172, 'desc' => 'ESCADA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => '', 'modelo' => '3,8M 13 DEGRAUS', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 239],
            ['nupatrimonio' => 14953, 'oc' => 56172, 'desc' => 'ESCADA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => '', 'modelo' => '3,8M 13 DEGRAUS', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 239],
            ['nupatrimonio' => 14954, 'oc' => 56172, 'desc' => 'ESCADA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => '', 'modelo' => '3,8M 13 DEGRAUS', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 239],
            ['nupatrimonio' => 14955, 'oc' => 56172, 'desc' => 'ESCADA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => '', 'modelo' => '3,8M 13 DEGRAUS', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 239],
            
            // APARADOR - 5 unidades
            ['nupatrimonio' => 37295, 'oc' => 56147, 'desc' => 'APARADOR DE GRAMA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => 'TRAMONTINA', 'modelo' => 'AP1500T', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 110],
            ['nupatrimonio' => 37296, 'oc' => 56147, 'desc' => 'APARADOR DE GRAMA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => 'TRAMONTINA', 'modelo' => 'AP1500T', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 110],
            ['nupatrimonio' => 37297, 'oc' => 56147, 'desc' => 'APARADOR DE GRAMA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => 'TRAMONTINA', 'modelo' => 'AP1500T', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 110],
            ['nupatrimonio' => 37298, 'oc' => 56147, 'desc' => 'APARADOR DE GRAMA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => 'TRAMONTINA', 'modelo' => 'AP1500T', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 110],
            ['nupatrimonio' => 37300, 'oc' => 56147, 'desc' => 'APARADOR DE GRAMA', 'obs' => '', 'projeto' => 940, 'local' => null, 'marca' => 'TRAMONTINA', 'modelo' => 'AP1500T', 'situacao' => 'EM USO', 'dtaq' => '2026-01-19', 'codobjeto' => 110],
            
            // NOTEBOOK - 3 unidades
            ['nupatrimonio' => 29435, 'oc' => 56208, 'desc' => 'NOTEBOOK', 'obs' => '', 'projeto' => 942, 'local' => null, 'marca' => 'LENOVO', 'modelo' => 'V15 G4 8GB 256GB', 'situacao' => 'EM USO', 'dtaq' => '2026-01-20', 'codobjeto' => 196],
            ['nupatrimonio' => 29436, 'oc' => 56208, 'desc' => 'NOTEBOOK', 'obs' => '', 'projeto' => 942, 'local' => null, 'marca' => 'LENOVO', 'modelo' => 'V15 G4 8GB 256GB', 'situacao' => 'EM USO', 'dtaq' => '2026-01-20', 'codobjeto' => 196],
            ['nupatrimonio' => 29437, 'oc' => 56208, 'desc' => 'NOTEBOOK', 'obs' => '', 'projeto' => 942, 'local' => null, 'marca' => 'LENOVO', 'modelo' => 'V15 G4 8GB 256GB', 'situacao' => 'EM USO', 'dtaq' => '2026-01-20', 'codobjeto' => 196],
        ];

        $this->info("\n=== IMPORTANDO PATRIMÔNIOS ===\n");

        $usuario = 'BEATRIZ.SC';
        $cdmatr = 182687;
        $dtop = date('Y-m-d');
        $sucesso = 0;
        $erros = 0;

        // Encontrar locais padrão
        $localPadrao940 = DB::table('locais_projeto')->where('tabfant_id', 940)->first(['cdlocal']);
        $localPadrao942 = DB::table('locais_projeto')->where('tabfant_id', 942)->first(['cdlocal']);

        foreach ($dados as $row) {
            try {
                $localFinal = $row['local'];
                if (!$localFinal && $row['projeto'] == 940 && $localPadrao940) {
                    $localFinal = $localPadrao940->cdlocal;
                } elseif (!$localFinal && $row['projeto'] == 942 && $localPadrao942) {
                    $localFinal = $localPadrao942->cdlocal;
                }
                
                Patrimonio::create([
                    'NUPATRIMONIO' => $row['nupatrimonio'],
                    'NUMOF' => $row['oc'],
                    'DEPATRIMONIO' => $row['desc'],
                    'DEHISTORICO' => $row['obs'],
                    'CDPROJETO' => $row['projeto'],
                    'CDLOCAL' => $localFinal,
                    'MARCA' => $row['marca'],
                    'MODELO' => $row['modelo'],
                    'SITUACAO' => strtoupper($row['situacao']),
                    'DTAQUISICAO' => $row['dtaq'],
                    'CODOBJETO' => $row['codobjeto'],
                    'CDMATRFUNCIONARIO' => $cdmatr,
                    'USUARIO' => $usuario,
                    'DTOPERACAO' => $dtop,
                ]);
                
                $this->line("✅ Pat. " . $row['nupatrimonio']);
                $sucesso++;
            } catch (Exception $e) {
                $this->error("❌ Pat. " . $row['nupatrimonio'] . ": " . $e->getMessage());
                $erros++;
            }
        }

        $this->info("\n✅ RESUMO: " . $sucesso . " patrimonios criados, " . $erros . " erros\n");
    }
}
