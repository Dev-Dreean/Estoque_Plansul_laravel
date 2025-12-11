<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
            
            // Detecta nome da coluna PK dinamicamente (NUSEQOBJ local vs NUSEQOBJETO servidor)
            $colunas = Schema::getColumnListing($resolved);
            $resolvedPK = in_array('NUSEQOBJ', $colunas) ? 'NUSEQOBJ' : 'NUSEQOBJETO';
        }
        
        $this->setTable($resolved);
        $this->primaryKey = $resolvedPK;
    }
    
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    // fillable dinâmico baseado na PK detectada
    public function getFillable()
    {
        $pk = in_array('NUSEQOBJ', Schema::getColumnListing($this->getTable())) ? 'NUSEQOBJ' : 'NUSEQOBJETO';
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
