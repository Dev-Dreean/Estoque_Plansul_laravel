<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ObjetoPatr extends Model
{
    // Resolve dinamicamente o nome da tabela (maiúscula/minúscula) para compatibilidade entre ambientes
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static $resolved = null;
        if ($resolved === null) {
            // Verifica primeiro maiúsculo (como nas migrations), depois minúsculo (como em alguns dumps)
            $resolved = Schema::hasTable('OBJETOPATR') ? 'OBJETOPATR' : (Schema::hasTable('objetopatr') ? 'objetopatr' : 'OBJETOPATR');
        }
        $this->setTable($resolved);
    }
    protected $primaryKey = 'NUSEQOBJETO';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['NUSEQOBJETO', 'NUSEQTIPOPATR', 'DEOBJETO'];

    public function tipo()
    {
        return $this->belongsTo(TipoPatr::class, 'NUSEQTIPOPATR', 'NUSEQTIPOPATR');
    }
}
