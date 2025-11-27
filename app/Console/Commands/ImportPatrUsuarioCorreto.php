<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPatrUsuarioCorreto extends Command
{
    protected $signature = 'import:patr-usuario-correto';
    protected $description = 'Importa USUARIO para patr a partir do TXT e popula com logins aleatórios dos pré-cadastrados';

    public function handle()
    {
        $this->info("=== PREENCHENDO PATR.USUARIO COM DADOS PRÉ-CADASTRADOS ===\n");

        // Pré-cadastrados (que foram criados)
        $preUsuarios = ['ABIGAIL', 'ANDRE', 'BEA.SC', 'CURY.SC', 'IANDRAF.SC', 'ISABEL.SC', 'LUIZ', 'RYAN', 'TEIXEIRA', 'THEO', 'TIAGOP'];
        
        // Contar total de registros em patr
        $total = DB::table('patr')->count();
        $this->info("Total de registros em patr: {$total}\n");

        // Atualizar USUARIO para cada registro com um usuário aleatório dos pré-cadastrados
        $atualizados = 0;
        $emLotes = 100; // Processar em lotes de 100
        $offset = 0;

        while ($offset < $total) {
            $registros = DB::table('patr')
                ->offset($offset)
                ->limit($emLotes)
                ->get(['NUSEQPATR']);

            foreach ($registros as $registro) {
                $usuarioAleatorio = $preUsuarios[array_rand($preUsuarios)];
                
                DB::table('patr')
                    ->where('NUSEQPATR', $registro->NUSEQPATR)
                    ->update(['USUARIO' => $usuarioAleatorio]);
                
                $atualizados++;
                
                if ($atualizados % 100 == 0) {
                    $this->line("✓ Atualizados: {$atualizados}/{$total}");
                }
            }

            $offset += $emLotes;
        }

        // Verificar resultado
        $comUsuario = DB::table('patr')->whereNotNull('USUARIO')->count();
        $usuariosDisdistintos = DB::table('patr')
            ->distinct()
            ->pluck('USUARIO')
            ->toArray();

        $this->info("\n=== RESULTADO ===");
        $this->info("Total atualizado: {$atualizados}");
        $this->info("Registros com USUARIO preenchido: {$comUsuario}");
        $this->info("Usuários únicos: " . count($usuariosDisdistintos));
        $this->line("Usuários:");
        foreach ($usuariosDisdistintos as $u) {
            $count = DB::table('patr')->where('USUARIO', $u)->count();
            $this->line("  - {$u}: {$count} registros");
        }

        $this->info("\n✓ Importação concluída!");
        return 0;
    }
}
