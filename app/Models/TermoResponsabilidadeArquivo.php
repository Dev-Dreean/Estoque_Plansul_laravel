<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermoResponsabilidadeArquivo extends Model
{
    use HasFactory;

    protected $table = 'termos_responsabilidade_arquivos';
    public $timestamps = false;

    protected $fillable = [
        'cdprojeto',
        'cdmatrfuncionario',
        'nome_arquivo',
        'caminho_arquivo',
        'total_itens',
        'origem',
        'gerado_por',
        'gerado_em',
    ];

    protected $casts = [
        'gerado_em' => 'datetime',
        'total_itens' => 'integer',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(TermoResponsabilidadeArquivoItem::class, 'termo_responsabilidade_arquivo_id');
    }
}
