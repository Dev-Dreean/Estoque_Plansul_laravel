<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('patr') || $this->columnExists('patr', 'NUMMESA')) {
            return;
        }

        Schema::table('patr', function (Blueprint $table) {
            $table->string('NUMMESA', 30)->nullable()->after('NMPLANTA');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('patr') || ! $this->columnExists('patr', 'NUMMESA')) {
            return;
        }

        Schema::table('patr', function (Blueprint $table) {
            $table->dropColumn('NUMMESA');
        });
    }

    private function columnExists(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.columns')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }
};
