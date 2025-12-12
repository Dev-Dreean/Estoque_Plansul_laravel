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

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'NUSEQPATR';
    }

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
        'DTAQUISICAO' => 'date:Y-m-d',
        'DTOPERACAO'  => 'date:Y-m-d',
        'DTBAIXA'     => 'date:Y-m-d',
        'DTGARANTIA'  => 'date:Y-m-d',
        'DTLAUDO'     => 'date:Y-m-d',
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
     * CDLOCAL armazena o cdlocal da tabela locais_projeto.
     */
    public function localProjeto(): BelongsTo
    {
        return $this->belongsTo(LocalProjeto::class, 'CDLOCAL', 'cdlocal');
    }

    /**
     * Relação com o Local (via LocalProjeto -> Tabfant).
     * Esta é a relação corrigida que busca o projeto através do local.
     * CDLOCAL do patr -> cdlocal da locais_projeto
     */
    public function local()
    {
        return $this->belongsTo(LocalProjeto::class, 'CDLOCAL', 'cdlocal');
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
        $user = null;
        $login = $this->USUARIO;

        if ($this->relationLoaded('creator') && $this->creator) {
            $user = $this->creator;
        } elseif (!empty($login)) {
            $cacheKey = 'login_user_' . $login;
            $user = Cache::remember($cacheKey, 300, fn () => User::where('NMLOGIN', $login)->first());
        }

        if (!$user && empty($login)) {
            return 'SISTEMA';
        }

        $fullName = $user->NOMEUSER ?? null;
        $uf = $user->UF ?? null;
        $uf = $uf ? mb_strtoupper(trim((string) $uf)) : null;

        // fallback: extrai UF do login (ex.: beatriz.sc => sc)
        if (!$uf && $login && preg_match('/\\.([a-z]{2})$/i', $login, $m)) {
            $uf = mb_strtoupper($m[1]);
        }

        $firstName = null;
        if ($fullName && is_string($fullName)) {
            $parts = array_values(array_filter(preg_split('/\s+/', trim($fullName)) ?: []));
            $firstName = $parts[0] ?? null;
        }

        if (!$firstName && $login) {
            if (preg_match('/^([^.]+)/', (string) $login, $m)) {
                $firstName = $m[1];
            } else {
                $firstName = $login;
            }
        }

        $baseRaw = $firstName ?: ($login ?? 'SISTEMA');
        $baseRaw = mb_strtoupper($baseRaw);

        // Se tiver UF, sempre mostra "NOME.UF" usando apenas a parte antes do primeiro ponto para evitar ".SC.SC"
        if ($uf) {
            $parts = explode('.', $baseRaw);
            $base = $parts[0] ?: $baseRaw;
            return $base . '.' . $uf;
        }

        // Sem UF: mostrar apenas a parte antes do ponto, se existir
        if (str_contains($baseRaw, '.')) {
            $baseRaw = explode('.', $baseRaw)[0] ?: $baseRaw;
        }

        return $baseRaw;
    }

    public function getDtaquisicaoPtBrAttribute(): ?string
    {
        if (!$this->DTAQUISICAO) {
            return null;
        }
        
        try {
            $date = is_string($this->attributes['DTAQUISICAO'] ?? null) 
                ? \Carbon\Carbon::parse($this->attributes['DTAQUISICAO'])
                : $this->DTAQUISICAO;
            
            // Validar se o ano é razoável (entre 1990 e próximos 5 anos)
            $year = $date->year;
            if ($year < 1990 || $year > 2030) {
                return null; // Retorna null para datas inválidas
            }
            
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getDtoperacaoPtBrAttribute(): ?string
    {
        if (!$this->DTOPERACAO) {
            return null;
        }
        
        try {
            $date = is_string($this->attributes['DTOPERACAO'] ?? null) 
                ? \Carbon\Carbon::parse($this->attributes['DTOPERACAO'])
                : $this->DTOPERACAO;
            
            // Validar se o ano é razoável (entre 1990 e próximos 5 anos)
            $year = $date->year;
            if ($year < 1990 || $year > 2030) {
                return null; // Retorna null para datas inválidas
            }
            
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
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

    /**
     * Retorna a UF (Estado) do patrimonio
     * 1️⃣  Usa UF do projeto (via CDPROJETO)
     * 2️⃣  Usa UF do local (via CDLOCAL)
     * 3️⃣  Usa UF armazenada diretamente (fallback)
     * 
     * Esse accessor permite usar: $patrimonio->uf_estado ou $patrimonio->uf
     */
    public function getUfEstadoAttribute(): ?string
    {
        // 1. Tentar obter de UF já armazenada
        if (!empty($this->UF)) {
            return $this->UF;
        }

        // 2. Tentar obter do Projeto (CDPROJETO)
        if (!empty($this->CDPROJETO)) {
            $projeto = $this->projeto;
            if ($projeto && !empty($projeto->UF)) {
                return $projeto->UF;
            }
        }

        // 3. Tentar obter do Local (CDLOCAL)
        if (!empty($this->CDLOCAL)) {
            $local = $this->local;
            if ($local && !empty($local->UF)) {
                return $local->UF;
            }
            // Se local está carregado, tentar via projeto do local
            if ($local && $local->relationLoaded('projeto')) {
                $projLocal = $local->projeto;
                if ($projLocal && !empty($projLocal->UF)) {
                    return $projLocal->UF;
                }
            }
        }

        return null;
    }

    /**
     * Alias para getUfEstadoAttribute
     */
    public function getUfAttribute(): ?string
    {
        return $this->getUfEstadoAttribute();
    }

    /**
     * Retorna informações de UF para debug/auditoria
     * Mostra de onde a UF foi obtida
     */
    public function getUfSourceAttribute(): array
    {
        $source = [];

        // Verificar cada fonte
        if (!empty($this->UF)) {
            $source['stored'] = $this->UF;
        }

        if (!empty($this->CDPROJETO)) {
            try {
                $proj = $this->projeto;
                if ($proj && !empty($proj->UF)) {
                    $source['projeto'] = $proj->UF;
                }
            } catch (\Exception $e) {
                // Ignorar erros de carregamento
            }
        }

        if (!empty($this->CDLOCAL)) {
            try {
                $loc = $this->local;
                if ($loc && !empty($loc->UF)) {
                    $source['local'] = $loc->UF;
                } elseif ($loc && $loc->relationLoaded('projeto')) {
                    $pLocal = $loc->projeto;
                    if ($pLocal && !empty($pLocal->UF)) {
                        $source['local_projeto'] = $pLocal->UF;
                    }
                }
            } catch (\Exception $e) {
                // Ignorar erros de carregamento
            }
        }

        return $source;
    }

    /**
     * Scope: Filtrar patrimonios por UF (Estado)
     * 
     * Prioridade de resolução:
     * 1. UF armazenada diretamente em patr.UF
     * 2. UF do local (CDLOCAL) - tem prioridade
     * 3. UF do projeto (CDPROJETO) - fallback
     * 
     * Uso:
     * - Patrimonio::byUf('SP')->get()
     * - Patrimonio::byUf(['SP', 'MG'])->get()
     */
    public function scopeByUf($query, $ufs)
    {
        if (empty($ufs)) {
            return $query;
        }

        // Garantir que é um array
        if (!is_array($ufs)) {
            $ufs = [$ufs];
        }

        // ✅ CORRIGIDO: Usar whereHas ou whereIn com lógica correta
        // Prioridade 1: UF armazenada diretamente
        // Prioridade 2: UF do local (tem prioridade sobre projeto)
        // Prioridade 3: UF do projeto (fallback)
        
        return $query->where(function ($q) use ($ufs) {
            // 1. Verificar UF diretamente armazenada
            $q->whereIn('UF', $ufs);
            
            // 2. OU verificar UF do local (tem prioridade)
            $q->orWhereIn('CDLOCAL', function ($subquery) use ($ufs) {
                $subquery->select('id')
                         ->from('locais_projeto')
                         ->whereIn('UF', $ufs);
            });
            
            // 3. OU verificar UF do projeto (apenas se não tem local com UF definida)
            $q->orWhereIn('CDPROJETO', function ($subquery) use ($ufs) {
                $subquery->select('id')
                         ->from('tabfant')
                         ->whereIn('UF', $ufs);
            });
        });
    }
}

