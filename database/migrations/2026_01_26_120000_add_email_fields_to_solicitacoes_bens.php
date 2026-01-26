<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->string('email_origem', 200)->nullable()->after('solicitante_matricula');
            $table->string('email_assunto', 200)->nullable()->after('email_origem');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropColumn(['email_origem', 'email_assunto']);
        });
    }
};
