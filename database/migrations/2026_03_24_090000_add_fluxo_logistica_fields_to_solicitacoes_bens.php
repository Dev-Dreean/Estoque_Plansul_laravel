<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->decimal('logistics_height_cm', 10, 2)->nullable()->after('tracking_code')->comment('Altura em cm');
            $table->decimal('logistics_width_cm', 10, 2)->nullable()->after('logistics_height_cm')->comment('Largura em cm');
            $table->decimal('logistics_length_cm', 10, 2)->nullable()->after('logistics_width_cm')->comment('Comprimento em cm');
            $table->decimal('logistics_weight_kg', 10, 3)->nullable()->after('logistics_length_cm')->comment('Peso em kg');
            $table->text('logistics_notes')->nullable()->after('logistics_weight_kg')->comment('Observações da logística');
            $table->unsignedBigInteger('logistics_registered_by_id')->nullable()->after('logistics_notes')->comment('Usuário que registrou logística');
            $table->timestamp('logistics_registered_at')->nullable()->after('logistics_registered_by_id')->comment('Data do registro logístico');
            $table->string('quote_transporter', 120)->nullable()->after('logistics_registered_at')->comment('Transportadora cotada');
            $table->decimal('quote_amount', 12, 2)->nullable()->after('quote_transporter')->comment('Valor da cotação');
            $table->string('quote_deadline', 80)->nullable()->after('quote_amount')->comment('Prazo estimado da cotação');
            $table->text('quote_notes')->nullable()->after('quote_deadline')->comment('Observações da cotação');
            $table->unsignedBigInteger('quote_registered_by_id')->nullable()->after('quote_notes')->comment('Usuário que registrou a cotação');
            $table->timestamp('quote_registered_at')->nullable()->after('quote_registered_by_id')->comment('Data do registro da cotação');
            $table->unsignedBigInteger('quote_approved_by_id')->nullable()->after('quote_registered_at')->comment('Solicitante que aprovou a cotação');
            $table->timestamp('quote_approved_at')->nullable()->after('quote_approved_by_id')->comment('Data da aprovação da cotação');
            $table->string('invoice_number', 100)->nullable()->after('quote_approved_at')->comment('Número da nota fiscal');
            $table->unsignedBigInteger('shipped_by_id')->nullable()->after('invoice_number')->comment('Usuário que registrou o envio');
            $table->timestamp('shipped_at')->nullable()->after('shipped_by_id')->comment('Data do envio');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropColumn([
                'logistics_height_cm',
                'logistics_width_cm',
                'logistics_length_cm',
                'logistics_weight_kg',
                'logistics_notes',
                'logistics_registered_by_id',
                'logistics_registered_at',
                'quote_transporter',
                'quote_amount',
                'quote_deadline',
                'quote_notes',
                'quote_registered_by_id',
                'quote_registered_at',
                'quote_approved_by_id',
                'quote_approved_at',
                'invoice_number',
                'shipped_by_id',
                'shipped_at',
            ]);
        });
    }
};
