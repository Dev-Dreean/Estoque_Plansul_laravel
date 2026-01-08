<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('usuario', function (Blueprint $table) {
                if (!Schema::hasColumn('usuario', 'needs_identity_update')) {
                    $table->boolean('needs_identity_update')->default(false)->after('password_policy_version');
                }
            });
        } catch (\Exception $e) {
            // KingHost MySQL antigo não tem generation_expression
            // Ignorar erro de schema check e tentar adicionar coluna diretamente
            \DB::statement('ALTER TABLE usuario ADD COLUMN needs_identity_update BOOLEAN DEFAULT FALSE AFTER password_policy_version');
        }
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (Schema::hasColumn('usuario', 'needs_identity_update')) {
                $table->dropColumn('needs_identity_update');
            }
        });
    }
};
