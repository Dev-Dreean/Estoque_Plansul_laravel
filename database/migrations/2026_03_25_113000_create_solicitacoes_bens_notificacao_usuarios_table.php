<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('solicitacoes_bens_notificacao_usuarios')) {
            Schema::create('solicitacoes_bens_notificacao_usuarios', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('usuario_id');
                $table->string('papel', 40);

                $table->unique(['usuario_id', 'papel'], 'sb_notif_usuario_papel_unique');
                $table->index(['papel', 'usuario_id'], 'sb_notif_papel_usuario_index');
            });
        }

        $mapa = [
            'TIAGOP' => ['triagem', 'medicao', 'envio'],
            'BEA.SC' => ['triagem', 'cotacao', 'envio'],
            'BRUNO' => ['liberacao'],
        ];

        foreach ($mapa as $login => $papeis) {
            $usuario = DB::table('usuario')
                ->select('NUSEQUSUARIO')
                ->where('NMLOGIN', $login)
                ->first();

            if (!$usuario) {
                continue;
            }

            foreach ($papeis as $papel) {
                DB::table('solicitacoes_bens_notificacao_usuarios')->updateOrInsert(
                    [
                        'usuario_id' => $usuario->NUSEQUSUARIO,
                        'papel' => $papel,
                    ],
                    []
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_bens_notificacao_usuarios');
    }
};
