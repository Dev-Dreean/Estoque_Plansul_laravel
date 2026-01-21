<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ObjetoPatr extends Model
{
    // Resolve dinamicamente o nome da tabela (maiúscula/minúscula) para compatibilidade entre ambientes
    protected $table;
    protected $primaryKey;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static $resolved = null;
        static $resolvedPK = null;
        
        if ($resolved === null) {
            // Verifica primeiro maiúsculo (como nas migrations), depois minúsculo (como em alguns dumps)
            $resolved = Schema::hasTable('OBJETOPATR') ? 'OBJETOPATR' : (Schema::hasTable('objetopatr') ? 'objetopatr' : 'OBJETOPATR');
            
            // Detecta nome da coluna PK: Local (NUSEQOBJ) vs KingHost (NUSEQOBJETO)
            // Via query SQL direta (Schema builder não funciona em KingHost com MySQL antigo)
            try {
                $result = DB::selectOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = 'PRI'",
                    [DB::getDatabaseName(), $resolved]);
                $resolvedPK = $result ? $result->COLUMN_NAME : 'NUSEQOBJETO';
            } catch (\Exception $e) {
                // Fallback se query falhar: usar o padrão do servidor (NUSEQOBJETO)
                $resolvedPK = 'NUSEQOBJETO';
            }
        }
        
        $this->setTable($resolved);
        $this->primaryKey = $resolvedPK;
    }
    
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    // fillable dinmico baseado na PK detectada
    public function getFillable()
    {
        $pk = $this->primaryKey ?? 'NUSEQOBJETO';
        return [$pk, 'NUSEQTIPOPATR', 'DEOBJETO'];
    }

    /**
     * Mutator para converter DEOBJETO em UPPERCASE ao salvar
     */
    public function setDEOBJETOAttribute($value)
    {
        $this->attributes['DEOBJETO'] = strtoupper($value);
    }

    public function tipo()
    {
        return $this->belongsTo(TipoPatr::class, 'NUSEQTIPOPATR', 'NUSEQTIPOPATR');
    }
}

