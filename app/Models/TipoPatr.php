<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TipoPatr extends Model
{
    // Resolve dinamicamente o nome da tabela para compatibilidade Linux/MySQL
    protected $table;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static $resolved = null;
        if ($resolved === null) {
            $resolved = Schema::hasTable('TIPOPATR') ? 'TIPOPATR' : (Schema::hasTable('tipopatr') ? 'tipopatr' : 'TIPOPATR');
        }
        $this->setTable($resolved);
    }
    protected $primaryKey = 'NUSEQTIPOPATR';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['NUSEQTIPOPATR', 'DETIPOPATR'];

    public function objetos()
    {
        return $this->hasMany(ObjetoPatr::class, 'NUSEQTIPOPATR', 'NUSEQTIPOPATR');
    }
}
