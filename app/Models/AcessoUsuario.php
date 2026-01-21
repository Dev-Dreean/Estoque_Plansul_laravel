<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcessoUsuario extends Model
{
    /**
     * Tabela associada ao modelo
     */
    protected $table = 'acessousuario';

    /**
     * Chave primária composta
     */
    protected $primaryKey = ['NUSEQTELA', 'CDMATRFUNCIONARIO'];

    /**
     * Indica se a chave primária é auto-incrementada
     */
    public $incrementing = false;

    /**
     * Timestamps não são usados nesta tabela
     */
    public $timestamps = false;

    /**
     * Campos que podem ser atribuídos em massa
     */
    protected $fillable = [
        'NUSEQTELA',
        'CDMATRFUNCIONARIO',
        'INACESSO',
    ];

    /**
     * Cast para tipos específicos
     */
    protected $casts = [
        'NUSEQTELA' => 'integer',
        'CDMATRFUNCIONARIO' => 'string',
        'INACESSO' => 'boolean', // 'S' = true, 'N' = false
    ];

    /**
     * Mutator para converter boolean em 'S'/'N' ao salvar
     */
    public function setINACESSOAttribute($value)
    {
        $this->attributes['INACESSO'] = $value ? 'S' : 'N';
    }

    /**
     * Accessor para converter 'S'/'N' em boolean ao ler
     */
    public function getINACESSOAttribute($value)
    {
        return $value === 'S';
    }

    /**
     * Relacionamento: pertence a um usuário
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    /**
     * Relacionamento: pertence a uma tela (termo/código)
     */
    public function tela()
    {
        return $this->belongsTo(TermoCodigo::class, 'NUSEQTELA', 'NUSEQTELA');
    }

    /**
     * Scope para filtrar apenas acessos ativos (INACESSO = 'S')
     */
    public function scopeAtivos($query)
    {
        return $query->where('INACESSO', 'S');
    }

    /**
     * Scope para filtrar por usuário
     */
    public function scopeDoUsuario($query, $cdMatrFuncionario)
    {
        return $query->where('CDMATRFUNCIONARIO', $cdMatrFuncionario);
    }

    /**
     * Scope para filtrar por tela
     */
    public function scopeDaTela($query, $nuseqtela)
    {
        return $query->where('NUSEQTELA', $nuseqtela);
    }
}

