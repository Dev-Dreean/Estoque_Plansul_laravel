<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Importa os dados corretos de locais_projeto e tabfant do KingHost
     * Sobrescreve os dados locais que estão quebrados/incorretos
     */
    public function up(): void
    {
        $backup_dir = database_path('../storage/backups');

        // Passo 1: Backup dos dados atuais ANTES de sobrescrever
        echo "\n🔄 [IMPORT] Fazendo backup dos dados atuais...\n";
        
        $local_locais = DB::table('locais_projeto')->get();
        $local_tabfant = DB::table('tabfant')->get();
        
        $timestamp = date('Y_m_d_His');
        File::put(
            "$backup_dir/pre_import_locais_projeto_$timestamp.json",
            json_encode($local_locais, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        File::put(
            "$backup_dir/pre_import_tabfant_$timestamp.json",
            json_encode($local_tabfant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        echo "✅ Backups criados\n";

        // Passo 2: Importar locais_projeto do KingHost
        echo "\n📥 [IMPORT] Importando locais_projeto do KingHost...\n";
        
        $locais_file = "$backup_dir/kinghost_locais_projeto.json";
        if (File::exists($locais_file)) {
            $locais_data = json_decode(File::get($locais_file), true);
            
            // Limpar tabela atual
            DB::table('locais_projeto')->truncate();
            
            // Inserir dados do KingHost
            foreach (array_chunk($locais_data, 100) as $chunk) {
                DB::table('locais_projeto')->insert($chunk);
            }
            
            echo "✅ " . count($locais_data) . " registros importados\n";
        } else {
            echo "⚠️  Arquivo kinghost_locais_projeto.json não encontrado\n";
        }

        // Passo 3: Importar tabfant (projetos) do KingHost
        echo "\n📥 [IMPORT] Importando tabfant (projetos) do KingHost...\n";
        
        $tabfant_file = "$backup_dir/kinghost_tabfant.json";
        if (File::exists($tabfant_file)) {
            $tabfant_data = json_decode(File::get($tabfant_file), true);
            
            // Limpar tabela atual
            DB::table('tabfant')->truncate();
            
            // Inserir dados do KingHost
            foreach (array_chunk($tabfant_data, 100) as $chunk) {
                DB::table('tabfant')->insert($chunk);
            }
            
            echo "✅ " . count($tabfant_data) . " registros importados\n";
        } else {
            echo "⚠️  Arquivo kinghost_tabfant.json não encontrado\n";
        }

        echo "\n✅ [IMPORT] Importação concluída!\n";
        echo "   Backups salvos em: $backup_dir/\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n⚠️  Reversão de import é arriscada. Restaure manualmente do backup se necessário.\n";
    }
};
