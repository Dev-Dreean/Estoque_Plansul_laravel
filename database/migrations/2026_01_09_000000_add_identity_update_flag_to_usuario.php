<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('usuario', 'needs_identity_update')) {
                $table->boolean('needs_identity_update')->default(false)->after('password_policy_version');
            }
        });
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
