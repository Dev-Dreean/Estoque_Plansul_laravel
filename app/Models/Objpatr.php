<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Objpatr extends Model
{
    use HasFactory;

    protected $table = 'objpatr';
    public $timestamps = false;
    protected $primaryKey = ['NUSEQOBJ', 'NUSEQTIPOPATR'];
    public $incrementing = false;

    protected $fillable = [
        'NUSEQTIPOPATR',
        'NUSEQOBJ',
        'DEOBJETO',
    ];
}