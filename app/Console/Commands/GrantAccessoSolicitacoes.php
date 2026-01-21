<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrantAccessoSolicitacoes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'solicitacoes:grant-acesso {--user= : Usuário específico (opcional)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Concede acesso à tela de Solicitações de Bens (1010) para usuários ativos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $telaId = 1010;
        
        $this->info('🚀 Concedendo acesso à tela 1010 (Solicitações de Bens)...');
        
        // Se usuário específico for informado
        if ($this->option('user')) {
            $usuario = User::where('NMLOGIN', $this->option('user'))->first();
            
            if (!$usuario) {
                $this->error("❌ Usuário '{$this->option('user')}' não encontrado.");
                return 1;
            }
            
            $usuarios = collect([$usuario]);
        } else {
            // Caso contrário, listar todos os usuários ativos
            $usuarios = User::where('LGATIVO', 'S')->get();
        }
        
        $this->info("\n📋 Total de usuários a processar: " . $usuarios->count());
        
        $adicionados = 0;
        $existentes = 0;
        
        foreach ($usuarios as $usuario) {
            $cdMatrFunc = $usuario->CDMATRFUNCIONARIO;
            
            $temAcesso = DB::table('acessousuario')
                ->where('CDMATRFUNCIONARIO', $cdMatrFunc)
                ->where('NUSEQTELA', $telaId)
                ->exists();
            
            if ($temAcesso) {
                $existentes++;
                $this->line("  ⏭️  {$usuario->NMLOGIN} (já tem acesso)");
            } else {
                DB::table('acessousuario')->insert([
                    'CDMATRFUNCIONARIO' => $cdMatrFunc,
                    'NUSEQTELA' => $telaId,
                    'INACESSO' => 'S',
                ]);
                $adicionados++;
                $this->line("  ✅ {$usuario->NMLOGIN} (novo acesso concedido)");
                
                Log::info("✅ [SOLICITACOES] Acesso concedido à tela 1010 para usuário: {$usuario->NMLOGIN}");
            }
        }
        
        $this->info("\n📊 Resultado:");
        $this->line("   ✅ Novos acessos: {$adicionados}");
        $this->line("   ⏭️  Já possuíam: {$existentes}");
        $this->line("   📦 Total processado: " . ($adicionados + $existentes));
        
        $this->info("\n✨ Concluído!");
        
        return 0;
    }
}
