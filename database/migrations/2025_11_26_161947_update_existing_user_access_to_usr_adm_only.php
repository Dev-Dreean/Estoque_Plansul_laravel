<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Atualiza todos os usuários existentes com a nova política de acessos:
     * - USR: apenas telas 1000 (Patrimônio) e 1001 (Gráficos)
     * - ADM: acesso a todas as telas (sem restrição de acessousuario)
     * - SUP: acesso a todas as telas (sem restrição de acessousuario)
     */
    public function up(): void
    {
        // Obter todos os usuários USR
        $usuariosUSR = DB::table('usuario')
            ->where('PERFIL', 'USR')
            ->pluck('CDMATRFUNCIONARIO')
            ->toArray();

        // Para cada usuário USR, remover todos os acessos e adicionar apenas 1000 e 1001
        foreach ($usuariosUSR as $matricula) {
            // Remover acessos antigos
            DB::table('acessousuario')
                ->where('CDMATRFUNCIONARIO', $matricula)
                ->delete();

            // Adicionar novos acessos (apenas 1000 e 1001)
            DB::table('acessousuario')->insert([
                ['CDMATRFUNCIONARIO' => $matricula, 'NUSEQTELA' => 1000, 'INACESSO' => 'S'],
                ['CDMATRFUNCIONARIO' => $matricula, 'NUSEQTELA' => 1001, 'INACESSO' => 'S'],
            ]);
        }

        // Para ADM e SUP, remover todos os acessos da tabela acessousuario
        // pois eles têm acesso a tudo por padrão
        DB::table('acessousuario')
            ->join('usuario', 'acessousuario.CDMATRFUNCIONARIO', '=', 'usuario.CDMATRFUNCIONARIO')
            ->whereIn('usuario.PERFIL', ['ADM', 'SUP'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não é possível reverter esta migração com precisão
        // pois não temos backup dos acessos anteriores
    }
};
