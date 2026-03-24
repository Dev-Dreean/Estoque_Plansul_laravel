<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->longText('quote_options_payload')
                ->nullable()
                ->after('quote_notes')
                ->comment('JSON com até N cotações registradas pela Beatriz');
            $table->unsignedTinyInteger('quote_selected_index')
                ->nullable()
                ->after('quote_options_payload')
                ->comment('Índice da cotação escolhida para liberação');
            $table->string('quote_tracking_type', 30)
                ->nullable()
                ->after('quote_selected_index')
                ->comment('Tipo de rastreio exigido pela cotação aprovada');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropColumn([
                'quote_options_payload',
                'quote_selected_index',
                'quote_tracking_type',
            ]);
        });
    }
};
