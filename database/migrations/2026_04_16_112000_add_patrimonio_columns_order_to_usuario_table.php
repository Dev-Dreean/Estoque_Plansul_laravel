<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('usuario', 'patrimonio_columns_order')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->json('patrimonio_columns_order')
                    ->nullable()
                    ->after('theme')
                    ->comment('Ordem personalizada de colunas no grid de patrimônios por usuário');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuario', 'patrimonio_columns_order')) {
            Schema::table('usuario', function (Blueprint $table) {
                $table->dropColumn('patrimonio_columns_order');
            });
        }
    }
};
