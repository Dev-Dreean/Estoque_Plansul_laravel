<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermoCodigo extends Model
{
    use HasFactory;

    protected $table = 'termo_codigos';

    protected $fillable = [
        'codigo',
        'created_by',
    ];
}

