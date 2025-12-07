<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckUsers extends Command
{
    protected $signature = 'check:users';
    protected $description = 'Verificar usuários BEA/BEATRIZ';

    public function handle()
    {
        echo "\n=== USUÁRIOS BEA/BEATRIZ ===\n\n";

        $users = DB::table('usuario')
            ->whereRaw("UPPER(NOMEUSER) LIKE '%BEA%'")
            ->get();

        foreach($users as $u) {
            echo "  - " . $u->NOMEUSER . " (CDMATR: " . ($u->CDMATRFUNCIONARIO ?? 'NULL') . ", Senha: " . ($u->SENHA ? 'SIM' : 'NÃO') . ", UF: " . ($u->UF ?? 'NULL') . ")\n";
        }

        echo "\n=== REGISTROS EM PATR ===\n\n";

        $patrimonios = DB::table('patr')
            ->whereRaw("UPPER(USUARIO) LIKE '%BEA%'")
            ->distinct()
            ->pluck('USUARIO');

        foreach($patrimonios as $user) {
            $count = DB::table('patr')->where('USUARIO', $user)->count();
            echo "  - " . $user . " (" . $count . " registros)\n";
        }

        return 0;
    }
}
