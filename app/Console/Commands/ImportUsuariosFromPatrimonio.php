<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ImportUsuariosFromPatrimonio extends Command
{
    protected $signature = 'import:usuarios-patrimonio';
    protected $description = 'Importa usuários do arquivo patrimonio.TXT e cria pre-registrations';

    public function handle()
    {
        $filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("=== IMPORTAÇÃO DE PATRIMONIO.TXT ===\n");

        // Ler linhas
        $lines = file($filePath, FILE_SKIP_EMPTY_LINES);

        // Encontrar coluna USUARIO
        $headerLine = null;
        $usuarioColumnPos = null;
        foreach ($lines as $idx => $line) {
            if (strpos($line, 'USUARIO') !== false) {
                $headerLine = $idx;
                $usuarioColumnPos = strpos($line, 'USUARIO');
                break;
            }
        }

        if ($headerLine === null) {
            $this->error("Coluna USUARIO não encontrada");
            return 1;
        }

        $this->info("✓ Coluna USUARIO identificada (linha " . ($headerLine + 1) . ")");

        // Parse e extração de usuários
        $usuarios = [];
        $startData = $headerLine + 3;

        for ($i = $startData; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            $startPos = $usuarioColumnPos;
            $substr = substr($line, $startPos, 20);
            
            $tokens = explode(' ', trim($substr));
            $usuario = $tokens[0];
            
            if (!empty($usuario) && 
                $usuario !== '<null>' && 
                preg_match('/^[A-Za-z0-9._\-]+$/', $usuario) &&
                strlen($usuario) > 2 &&
                strlen($usuario) < 50
            ) {
                $usuarios[$usuario] = true;
            }
        }

        $usuariosUnicos = array_keys($usuarios);
        sort($usuariosUnicos);

        $this->info("✓ Encontrados " . count($usuariosUnicos) . " usuários únicos\n");

        // Verificar quais já existem
        $existentes = User::whereIn('NMLOGIN', $usuariosUnicos)->pluck('NMLOGIN')->toArray();
        $novosUsuarios = array_diff($usuariosUnicos, $existentes);

        $this->info("=== RESUMO ===");
        $this->info("Usuários já existentes: " . count($existentes));
        foreach ($existentes as $e) {
            $this->line("  ✓ {$e}");
        }

        $this->info("\nNovos usuários a criar: " . count($novosUsuarios));
        foreach ($novosUsuarios as $n) {
            $this->line("  + {$n}");
        }

        // Criar pre-registrations
        if (count($novosUsuarios) > 0) {
            $this->info("\n=== CRIANDO PRE-REGISTRATIONS ===");
            
            foreach ($novosUsuarios as $login) {
                try {
                    $user = new User();
                    $user->NMLOGIN = $login;
                    $user->NOMEUSER = "{$login} (PRE)";
                    $user->PERFIL = 'USR';
                    $user->SENHA = 'temporaria123';
                    $user->LGATIVO = 1;
                    $user->save();
                    
                    $this->line("✓ Criado: {$login}");
                } catch (\Exception $e) {
                    $this->error("✗ Erro ao criar {$login}: " . $e->getMessage());
                }
            }
        }

        $this->info("\n=== IMPORTAÇÃO CONCLUÍDA ===");
        $this->info("Total de usuários criados: " . count($novosUsuarios));
        $this->info("Total de usuários existentes: " . count($existentes));
        $this->info("Total geral: " . count($usuariosUnicos));

        return 0;
    }
}
