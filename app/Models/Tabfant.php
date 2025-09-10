<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tabfant extends Model {
    protected $table = 'tabfant';
    public $timestamps = false;
    protected $fillable = ['CDPROJETO', 'NOMEPROJETO', 'LOCAL'];
}