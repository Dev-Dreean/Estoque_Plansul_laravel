<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConfigureGestorAccess extends Command
{
    protected $signature = 'colabs:configure-gestores';
    protected $description = 'Configure Colaboradores tela access for Tiago, Beatriz, Bruno';

    public function handle()
    {
        $gestores = ['TIAGO', 'BEATRIZ', 'BRUNO'];
        $this->info('Configurando acesso à tela de Colaboradores...');

        foreach ($gestores as $nome) {
            $func = DB::table('funcionarios')
                ->whereRaw('UPPER(NMFUNCIONARIO) LIKE ?', ['%' . strtoupper($nome) . '%'])
                ->first('CDMATRFUNCIONARIO', 'NMFUNCIONARIO');

            if (!$func) {
                $this->warn("✗ {$nome}: não encontrado");
                continue;
            }

            $usuario = DB::table('usuario')
                ->where('CDMATRFUNCIONARIO', $func->CDMATRFUNCIONARIO)
                ->first();

            if (!$usuario) {
                $this->warn("✗ {$func->NMFUNCIONARIO}: sem usuário");
                continue;
            }

            DB::table('acessousuario')->updateOrInsert(
                [
                    'CDMATRFUNCIONARIO' => $func->CDMATRFUNCIONARIO,
                    'NUSEQTELA' => 1011,
                ],
                ['INACESSO' => 'S']
            );

            $this->info("✓ {$func->NMFUNCIONARIO} ({$func->CDMATRFUNCIONARIO}) → Tela 1011 OK");
        }

        $this->info('Concluído!');
    }
}
