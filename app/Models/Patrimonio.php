<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Patrimonio extends Model
{
    use HasFactory;

    protected $table = 'patr';
    protected $primaryKey = 'NUSEQPATR';
    public $timestamps = false;

    protected $fillable = [
        'NUPATRIMONIO',
        'SITUACAO',
        'TIPO',
        'MARCA',
        'MODELO',
        'CARACTERISTICAS',
        'DIMENSAO',
        'COR',
        'NUSERIE',
        'CDLOCAL',
        'DTAQUISICAO',
        'DTBAIXA',
        'DTGARANTIA',
        'DEHISTORICO',
        'DTLAUDO',
        'DEPATRIMONIO',
        'CDMATRFUNCIONARIO',
        'CDLOCALINTERNO',
        'CDPROJETO',
        'USUARIO',
        'DTOPERACAO',
        'FLCONFERIDO',
        'NUMOF',
        'CODOBJETO',
        'NMPLANTA'
    ];

    protected $casts = [
        'DTAQUISICAO' => 'date',
        'DTOPERACAO'  => 'date',
        'DTBAIXA'     => 'date',
        'DTGARANTIA'  => 'date',
        'DTLAUDO'     => 'date',
    ];

    protected $appends = ['projeto_correto'];

    /**
     * Mutators para converter campos em UPPERCASE ao salvar
     */
    public function setSITUACAOAttribute($value)
    {
        $this->attributes['SITUACAO'] = strtoupper($value);
    }

    public function setMARCAAttribute($value)
    {
        $this->attributes['MARCA'] = strtoupper($value);
    }

    public function setMODELOAttribute($value)
    {
        $this->attributes['MODELO'] = strtoupper($value);
    }

    public function setCARACTERISTICASAttribute($value)
    {
        $this->attributes['CARACTERISTICAS'] = strtoupper($value);
    }

    public function setDIMENSAOAttribute($value)
    {
        $this->attributes['DIMENSAO'] = strtoupper($value);
    }

    public function setCORAttribute($value)
    {
        $this->attributes['COR'] = strtoupper($value);
    }

    public function setDEPATRIMONIOAttribute($value)
    {
        $this->attributes['DEPATRIMONIO'] = strtoupper($value);
    }

    public function setDEHISTORICOAttribute($value)
    {
        $this->attributes['DEHISTORICO'] = strtoupper($value);
    }

    public function setNUSERIEAttribute($value)
    {
        $this->attributes['NUSERIE'] = strtoupper($value);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    /**
     * Relação com o Local do Projeto.
     * CDLOCAL armazena o ID da tabela locais_projeto.
     */
    public function localProjeto(): BelongsTo
    {
        return $this->belongsTo(LocalProjeto::class, 'CDLOCAL', 'id');
    }

    /**
     * Relação com o Local (via LocalProjeto -> Tabfant).
     * Esta é a relação corrigida que busca o projeto através do local.
     */
    public function local()
    {
        return $this->belongsTo(LocalProjeto::class, 'CDLOCAL', 'id');
    }

    /**
     * Relação com o Projeto diretamente.
     * CDPROJETO armazena o código do projeto (não o ID).
     */
    public function projeto()
    {
        return $this->belongsTo(Tabfant::class, 'CDPROJETO', 'CDPROJETO');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'USUARIO', 'NMLOGIN');
    }

    public function getCadastradoPorNomeAttribute(): string
    {
        $fullName = null;

        if ($this->relationLoaded('creator') && $this->creator && $this->creator->NOMEUSER) {
            $fullName = $this->creator->NOMEUSER;
        } elseif (!empty($this->USUARIO)) {
            $cacheKey = 'login_nome_' . $this->USUARIO;
            $fullName = Cache::remember($cacheKey, 300, function () {
                return optional(User::where('NMLOGIN', $this->USUARIO)->first())->NOMEUSER ?? $this->USUARIO;
            });
        } else {
            return 'SISTEMA';
        }

        // Formatação: apenas primeiro e último nome
        if ($fullName && is_string($fullName)) {
            $parts = preg_split('/\s+/', trim($fullName));
            $parts = array_values(array_filter($parts));
            if (count($parts) === 0) {
                return $fullName;
            } elseif (count($parts) === 1) {
                return $parts[0];
            } else {
                return $parts[0] . ' ' . $parts[count($parts) - 1];
            }
        }

        return $fullName ?? 'SISTEMA';
    }

    public function getDtaquisicaoPtBrAttribute(): ?string
    {
        return $this->DTAQUISICAO ? $this->DTAQUISICAO->format('d/m/Y') : null;
    }

    public function getDtoperacaoPtBrAttribute(): ?string
    {
        return $this->DTOPERACAO ? $this->DTOPERACAO->format('d/m/Y') : null;
    }

    /**
     * Retorna o CDPROJETO correto - preferindo o do local se disponível
     * Isso garante consistência entre o grid e o formulário de edição
     */
    public function getProjetoCorretoAttribute(): ?string
    {
        // Se o local está carregado e tem projeto, usar dele
        if ($this->relationLoaded('local') && $this->local && $this->local->relationLoaded('projeto') && $this->local->projeto) {
            return $this->local->projeto->CDPROJETO;
        }

        // Senão, retornar o CDPROJETO armazenado diretamente
        return $this->CDPROJETO;
    }
}
