<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renomeia a coluna FLTERMORESPONSABILIDADE → VOLTAGEM e altera o tipo para varchar(20).
     * Reutiliza a coluna existente sem precisar criar nova coluna.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE patr
            CHANGE FLTERMORESPONSABILIDADE VOLTAGEM VARCHAR(20) NULL
            COMMENT 'Voltagem do equipamento (ex: 110V, 220V, Bivolt)'
        ");

        // Limpa os valores antigos S/N que não fazem sentido para voltagem
        DB::table('patr')
            ->whereIn('VOLTAGEM', ['S', 'N', ''])
            ->orWhereNull('VOLTAGEM')
            ->update(['VOLTAGEM' => null]);
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE patr
            CHANGE VOLTAGEM FLTERMORESPONSABILIDADE CHAR(1) NULL
            DEFAULT 'N'
        ");
    }
};
