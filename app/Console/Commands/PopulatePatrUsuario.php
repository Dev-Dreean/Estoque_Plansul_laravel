<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulatePatrUsuario extends Command
{
    protected $signature = 'populate:patr-usuario';
    protected $description = 'Popula a coluna patr.USUARIO com os dados extraídos do patrimonio.TXT';

    public function handle()
    {
        $filePath = "C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT";

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("=== POPULAÇÃO DE PATR.USUARIO ===\n");

        // Ler linhas
        $lines = file($filePath, FILE_SKIP_EMPTY_LINES);

        // Encontrar colunas USUARIO e número da coluna de ID/chave primária
        $headerLine = null;
        $usuarioColumnPos = null;
        $cdcolunaPos = null;

        foreach ($lines as $idx => $line) {
            if (strpos($line, 'USUARIO') !== false) {
                $headerLine = $idx;
                $usuarioColumnPos = strpos($line, 'USUARIO');
                $cdcolunaPos = strpos($line, 'CDCOLU'); // Coluna de ID primário
                break;
            }
        }

        if ($headerLine === null) {
            $this->error("Coluna USUARIO não encontrada");
            return 1;
        }

        $this->info("✓ Colunas identificadas");
        $this->info("  USUARIO (pos {$usuarioColumnPos})");
        if ($cdcolunaPos !== null) {
            $this->info("  CDCOLU (pos {$cdcolunaPos})");
        }

        // Parse de dados e atualização
        $atualizacoes = 0;
        $startData = $headerLine + 3;
        $registrosProcessados = [];

        for ($i = $startData; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Extrair USUARIO
            $substr = substr($line, $usuarioColumnPos, 20);
            $tokens = explode(' ', trim($substr));
            $usuario = $tokens[0];
            
            // Validar
            if (!empty($usuario) && 
                $usuario !== '<null>' && 
                preg_match('/^[A-Za-z0-9._\-]+$/', $usuario) &&
                strlen($usuario) > 2 &&
                strlen($usuario) < 50
            ) {
                // Extrair CDCOLU (ID primário)
                if ($cdcolunaPos !== null) {
                    $cdSubstr = substr($line, $cdcolunaPos, 15);
                    $cdTokens = explode(' ', trim($cdSubstr));
                    $cdcolu = (int)$cdTokens[0];
                    
                    if ($cdcolu > 0) {
                        $registrosProcessados[] = [
                            'CDCOLU' => $cdcolu,
                            'USUARIO' => $usuario,
                        ];
                    }
                }
            }
        }

        // Atualizar registros em lote
        if (count($registrosProcessados) > 0) {
            $this->info("\n✓ " . count($registrosProcessados) . " registros para atualizar");
            
            // Usar transação
            DB::beginTransaction();
            
            foreach ($registrosProcessados as $reg) {
                try {
                    $updated = DB::table('patr')
                        ->where('CDCOLU', $reg['CDCOLU'])
                        ->update(['USUARIO' => $reg['USUARIO']]);
                    
                    if ($updated > 0) {
                        $atualizacoes++;
                    }
                } catch (\Exception $e) {
                    $this->warn("Erro ao atualizar CDCOLU {$reg['CDCOLU']}: " . $e->getMessage());
                }
            }
            
            DB::commit();
        }

        // Verificar resultado
        $totalComUsuario = DB::table('patr')->where('USUARIO', '!=', null)->where('USUARIO', '!=', '')->count();
        $usuariosDodistintos = DB::table('patr')
            ->where('USUARIO', '!=', null)
            ->where('USUARIO', '!=', '')
            ->distinct()
            ->pluck('USUARIO')
            ->toArray();

        $this->info("\n=== RESULTADO ===");
        $this->info("Registros atualizados: {$atualizacoes}");
        $this->info("Total de registros com USUARIO preenchido: {$totalComUsuario}");
        $this->info("Usuários únicos: " . count($usuariosDodistintos));
        foreach ($usuariosDodistintos as $u) {
            $this->line("  - {$u}");
        }

        return 0;
    }
}
