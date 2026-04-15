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
        // IDs dos gestores (encontrados via query do banco)
        $gestores = [185895, 182687, 11829]; // Tiago Pacheco, Beatriz Patricia, Bruno de Azevedo
        $this->info('Configurando acesso à tela de Colaboradores (1011)...');

        foreach ($gestores as $matricula) {
            $func = DB::table('funcionarios')
                ->select('CDMATRFUNCIONARIO', 'NMFUNCIONARIO')
                ->where('CDMATRFUNCIONARIO', $matricula)
                ->first();

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
