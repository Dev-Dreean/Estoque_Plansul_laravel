<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove dados extras do campo NMFUNCIONARIO
        // O padrão é: "NOME COMPLETO                                            DD/MM/YYYY    0"
        // Precisamos deixar apenas "NOME COMPLETO"
        
        // Abordagem: remover tudo após 2 ou mais espaços em branco seguidos
        $funcionarios = DB::table('funcionarios')->get();
        
        foreach ($funcionarios as $func) {
            // Remove espaços extras e data/número no final
            $nome = trim($func->NMFUNCIONARIO);
            // Remove tudo após 2 espaços seguidos
            $nome = preg_replace('/\s{2,}.*$/', '', $nome);
            $nome = trim($nome);
            
            DB::table('funcionarios')
                ->where('CDMATRFUNCIONARIO', $func->CDMATRFUNCIONARIO)
                ->update(['NMFUNCIONARIO' => $nome]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não é possível restaurar os dados originais neste caso
        // Pois não temos como saber qual era a data original
    }
};
