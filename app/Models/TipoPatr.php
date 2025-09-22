<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPatr extends Model
{
    use HasFactory;

    protected $table = 'TIPOPATR';
    protected $primaryKey = 'NUSEQTIPOPATR';
    public $timestamps = false; // A tabela não tem created_at/updated_at

    protected $fillable = [
        'NUSEQTIPOPATR',
        'DETIPOPATR',
    ];
}
