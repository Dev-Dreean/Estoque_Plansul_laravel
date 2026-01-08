<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Cria índices para otimizar buscas
     */
    public function up(): void
    {
        // Usar try-catch para evitar erro se índice já existe
        try {
            DB::statement('ALTER TABLE objeto_patr ADD INDEX idx_nuseqobjeto (NUSEQOBJETO)');
        } catch (\Exception $e) {
            // Índice já existe ou erro menor
        }

        try {
            DB::statement('ALTER TABLE objeto_patr ADD INDEX idx_deobjeto (DEOBJETO(50))');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE tabfant ADD INDEX idx_cdprojeto (CDPROJETO)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE tabfant ADD INDEX idx_nomeprojeto (NOMEPROJETO(100))');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE locais_projeto ADD INDEX idx_tabfant_id (tabfant_id)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE locais_projeto ADD INDEX idx_cdlocal (cdlocal)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE locais_projeto ADD INDEX idx_flativo (flativo)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio ADD INDEX idx_nupatrimonio (NUPATRIMONIO)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio ADD INDEX idx_depatrimonio (DEPATRIMONIO(100))');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio ADD INDEX idx_situacao (SITUACAO)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio ADD INDEX idx_nmplanta (NMPLANTA)');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio ADD INDEX idx_cdmatrfuncionario (CDMATRFUNCIONARIO)');
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE objeto_patr DROP INDEX idx_nuseqobjeto');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE objeto_patr DROP INDEX idx_deobjeto');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE tabfant DROP INDEX idx_cdprojeto');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE tabfant DROP INDEX idx_nomeprojeto');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE locais_projeto DROP INDEX idx_tabfant_id');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE locais_projeto DROP INDEX idx_cdlocal');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE locais_projeto DROP INDEX idx_flativo');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio DROP INDEX idx_nupatrimonio');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio DROP INDEX idx_depatrimonio');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio DROP INDEX idx_situacao');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio DROP INDEX idx_nmplanta');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE patrimonio DROP INDEX idx_cdmatrfuncionario');
        } catch (\Exception $e) {}
    }
};
