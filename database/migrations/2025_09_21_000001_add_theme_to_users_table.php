<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration originalmente criada apontando para tabela 'users', que não existe neste projeto
// (modelo de usuário usa tabela 'usuario'). Para evitar falhas futuras de migrate, neutralizamos.
// Mantida para integridade da sequência de migrations sem efeitos colaterais.
return new class extends Migration {
    public function up(): void
    {
        // Intencionalmente vazio.
    }

    public function down(): void
    {
        // Nada a reverter.
    }
};
