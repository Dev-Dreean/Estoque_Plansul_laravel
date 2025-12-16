<?php

namespace App\Services;

use App\Models\Patrimonio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PatrimonioService
{
    public function listarParaIndex(Request $request, User $user, int $perPage = 30): array
    {
        Log::info('[PatrimonioService] Montando listagem index', [
            'user' => $user->NMLOGIN ?? null,
            'params' => $request->all(),
        ]);

        $perPage = max(10, min($perPage, 500));
        $query = $this->montarConsultaFiltrada($request, $user);

        $patrimonios = $query->paginate($perPage)->withQueryString();
        $showEmpty = (bool) $request->boolean('show_empty_columns', false);

        [$visibleColumns, $hiddenColumns] = $this->detectarColunasVisiveis($patrimonios->items(), $showEmpty);

        return [
            'patrimonios' => $patrimonios,
            'visibleColumns' => $visibleColumns,
            'hiddenColumns' => $hiddenColumns,
            'showEmptyColumns' => $showEmpty,
        ];
    }

    public function montarConsultaFiltrada(Request $request, User $user): Builder
    {
        Log::info('[PatrimonioService] Consulta filtrada iniciada', [
            'user' => $user->NMLOGIN ?? null,
            'perfil' => $user->PERFIL ?? null,
        ]);

        $query = Patrimonio::with(['funcionario', 'local.projeto', 'creator']);

        // TODOS podem ver TODOS os patrimônios (sem restrição de supervisão)
        // Filtros aplicados apenas via formulário de busca

        $this->aplicarFiltroCadastradores($query, $request, $user);
        $this->aplicarFiltrosPrincipais($query, $request);
        $this->aplicarOrdenacao($query, $request, $user);

        Log::info('[PatrimonioService] Consulta pronta', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        return $query;
    }

    public function listarCadastradoresParaFiltro(User $user): Collection
    {
        // TODOS podem ver TODOS os cadastradores (sem restrição de supervisão)
        $logins = Patrimonio::query()
            ->selectRaw("DISTINCT COALESCE(NULLIF(TRIM(USUARIO), ''), 'SISTEMA') as login")
            ->pluck('login')
            ->filter()
            ->map(fn($v) => strtoupper(trim((string) $v)))
            ->unique()
            ->values();

        if (!$logins->contains(fn($v) => strcasecmp($v, 'SISTEMA') === 0)) {
            $logins->push('SISTEMA');
        }

        $logins = $logins->unique()->values();

        $usuarios = User::whereIn(DB::raw('UPPER(NMLOGIN)'), $logins->all())
            ->get(['NMLOGIN', 'NOMEUSER', 'CDMATRFUNCIONARIO'])
            ->keyBy(fn($u) => strtoupper($u->NMLOGIN));

        return $logins
            ->map(function ($login) use ($usuarios) {
                $key = strtoupper($login);
                $dbUser = $usuarios[$key] ?? null;
                $nome = $dbUser->NOMEUSER ?? ($key === 'SISTEMA' ? 'Sistema' : $login);

                return (object) [
                    'CDMATRFUNCIONARIO' => $dbUser->CDMATRFUNCIONARIO ?? null,
                    'NOMEUSER' => $nome,
                    'NMLOGIN' => $login,
                ];
            })
            ->sortBy('NOMEUSER', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function listarDisponiveisParaTermo(Request $request, int $perPage = 30)
    {
        $perPage = max(10, min($perPage, 200));

        return Patrimonio::whereNull('NMPLANTA')
            ->orderBy('DEPATRIMONIO')
            ->paginate($perPage, ['*'], 'disponiveisPage')
            ->withQueryString()
            ->fragment('atribuir-termo');
    }

    public function listar(array $filtros = [], int $perPage = 15)
    {
        Log::info('[PatrimonioService] Listando patrimonios', [
            'filtros' => $filtros,
            'perPage' => $perPage,
        ]);
        
        $query = Patrimonio::query()
            ->with(['usuario', 'localprojeto', 'objeto', 'situacao']);
        
        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('NUPATRIMONIO', 'like', "%{$search}%")
                  ->orWhere('DEPATRIMONIO', 'like', "%{$search}%")
                  ->orWhere('MODELO', 'like', "%{$search}%")
                  ->orWhere('MARCA', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filtros['situacao'])) {
            $query->where('CDSITUACAO', $filtros['situacao']);
        }
        
        if (!empty($filtros['usuario_id'])) {
            $query->where('NUSEQPESSOA', $filtros['usuario_id']);
        }
        
        if (!empty($filtros['projeto_id'])) {
            $query->where('CDLOCALPROJETO', $filtros['projeto_id']);
        }
        
        $sortField = $filtros['sort'] ?? 'DTOPERACAO';
        $sortDirection = $filtros['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage)->withQueryString();
    }
    
    public function buscarPorId(int $id): ?Patrimonio
    {
        return Patrimonio::with(['usuario', 'localprojeto', 'objeto', 'situacao'])
            ->where('NUSEQPATR', $id)
            ->first();
    }
    
    public function criar(array $dados, int $usuarioId): Patrimonio
    {
        Log::info('[PatrimonioService] Criando patrimonio', [
            'dados' => $dados,
            'usuario_id' => $usuarioId
        ]);
        
        DB::beginTransaction();
        try {
            $patrimonio = Patrimonio::create(array_merge($dados, [
                'NUCADASTRADOR' => $usuarioId,
                'DTCADASTRO' => now(),
                'DTOPERACAO' => now()
            ]));
            
            DB::commit();
            
            Log::info('[PatrimonioService] Patrimonio criado', [
                'id' => $patrimonio->NUSEQPATR
            ]);
            
            return $patrimonio;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PatrimonioService] Erro ao criar patrimonio', [
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function atualizar(int $id, array $dados): Patrimonio
    {
        Log::info('[PatrimonioService] Atualizando patrimonio', [
            'id' => $id,
            'dados' => $dados
        ]);
        
        DB::beginTransaction();
        try {
            $patrimonio = $this->buscarPorId($id);
            
            if (!$patrimonio) {
                throw new \Exception("Patrimonio #{$id} nao encontrado");
            }
            
            $patrimonio->update(array_merge($dados, [
                'DTOPERACAO' => now()
            ]));
            
            DB::commit();
            
            Log::info('[PatrimonioService] Patrimonio atualizado', [
                'id' => $patrimonio->NUSEQPATR
            ]);
            
            return $patrimonio->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PatrimonioService] Erro ao atualizar patrimonio', [
                'id' => $id,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function deletar(int $id, int $usuarioId): bool
    {
        Log::info('[PatrimonioService] Deletando patrimonio', [
            'id' => $id,
            'usuario_id' => $usuarioId
        ]);
        
        DB::beginTransaction();
        try {
            $patrimonio = $this->buscarPorId($id);
            
            if (!$patrimonio) {
                throw new \Exception("Patrimonio #{$id} nao encontrado");
            }
            
            $deleted = $patrimonio->delete();
            
            DB::commit();
            
            Log::info('[PatrimonioService] Patrimonio deletado', [
                'id' => $id,
                'resultado' => $deleted
            ]);
            
            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PatrimonioService] Erro ao deletar patrimonio', [
                'id' => $id,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function estatisticas(): array
    {
        return [
            'total' => Patrimonio::count(),
            'ativos' => Patrimonio::where('CDSITUACAO', 1)->count(),
            'baixados' => Patrimonio::where('CDSITUACAO', 2)->count(),
            'em_manutencao' => Patrimonio::where('CDSITUACAO', 3)->count(),
            'por_usuario' => Patrimonio::select('NUSEQPESSOA', DB::raw('count(*) as total'))
                ->groupBy('NUSEQPESSOA')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
        ];
    }

    protected function aplicarFiltroCadastradores(Builder $query, Request $request, User $user): void
    {
        $multi = $request->input('cadastrados_por', []);
        if (is_string($multi)) {
            $multi = array_filter(array_map('trim', explode(',', $multi)));
        }

        $multi = collect($multi)
            ->map(fn($v) => trim((string) $v))
            ->filter()
            ->unique(fn($v) => mb_strtolower($v));

        if ($multi->isNotEmpty()) {
            Log::info('[PatrimonioService] Filtro multi cadastradores', ['valores' => $multi]);

            $includeSistema = false;
            $logins = [];

            foreach ($multi as $valor) {
                if (strcasecmp($valor, 'SISTEMA') === 0) {
                    $includeSistema = true;
                    continue;
                }
                $logins[] = mb_strtolower($valor);
            }

            $query->where(function ($q) use ($logins, $includeSistema) {
                $applied = false;

                if ($includeSistema) {
                    $q->whereNull('USUARIO')
                        ->orWhereRaw('LOWER(USUARIO) = ?', ['sistema']);
                    $applied = true;
                }

                foreach ($logins as $login) {
                    if (!$applied) {
                        $q->whereRaw('LOWER(USUARIO) = ?', [$login]);
                        $applied = true;
                    } else {
                        $q->orWhereRaw('LOWER(USUARIO) = ?', [$login]);
                    }
                }
            });

            return;
        }

        if ($request->filled('cadastrado_por')) {
            $valorFiltro = trim((string) $request->input('cadastrado_por'));

            if ($valorFiltro === '__TODOS__') {
                return;
            }

            if (!($user->isGod() || $user->PERFIL === 'ADM')) {
                $allowed = [strtoupper(trim((string) ($user->NMLOGIN ?? ''))), 'SISTEMA'];
                if (!empty($user->CDMATRFUNCIONARIO)) {
                    $allowed[] = (string) $user->CDMATRFUNCIONARIO;
                }
                if (!in_array(strtoupper($valorFiltro), array_map('strtoupper', $allowed))) {
                    $valorFiltro = null;
                }
            }

            if ($valorFiltro) {
                if (strcasecmp($valorFiltro, 'SISTEMA') === 0) {
                    $query->where(function ($q) {
                        $q->whereNull('USUARIO')
                            ->orWhereRaw('LOWER(USUARIO) = ?', ['sistema']);
                    });
                } else {
                    $loginFiltro = $valorFiltro;
                    $loginPorMatricula = null;

                    if (is_numeric($valorFiltro)) {
                        $usuarioFiltro = User::where('CDMATRFUNCIONARIO', $valorFiltro)->first();
                        $loginPorMatricula = $usuarioFiltro->NMLOGIN ?? null;
                    }

                    $query->where(function ($q) use ($loginFiltro, $loginPorMatricula, $valorFiltro) {
                        $q->whereRaw('LOWER(USUARIO) = ?', [mb_strtolower($loginFiltro)]);

                        if ($loginPorMatricula) {
                            $q->orWhereRaw('LOWER(USUARIO) = ?', [mb_strtolower($loginPorMatricula)]);
                        }

                        if (is_numeric($valorFiltro)) {
                            $q->orWhere('CDMATRFUNCIONARIO', $valorFiltro);
                        }
                    });
                }
            }
        }
    }

    protected function aplicarFiltrosPrincipais(Builder $query, Request $request): void
    {
        if ($request->filled('nupatrimonio')) {
            $val = trim((string) $request->input('nupatrimonio'));
            if ($val !== '') {
                // Filtrar por prefixo NUPATRIMONIO e ordenar numericamente
                // Exemplo: "6" encontrará 6, 60-69, 160, 260, etc (em ordem numérica crescente)
                $query->whereRaw('CAST(NUPATRIMONIO AS CHAR) LIKE ?', [$val . '%'])
                      ->orderByRaw('CAST(NUPATRIMONIO AS UNSIGNED) ASC');
            }
        }

        if ($request->filled('cdprojeto')) {
            $val = trim((string) $request->input('cdprojeto'));
            if ($val !== '') {
                // Otimização: buscar locais do projeto uma única vez e evitar subqueries pesadas por linha
                $locaisProjeto = collect();
                try {
                    $projeto = \App\Models\Tabfant::where('CDPROJETO', $val)->first(['id']);
                    if ($projeto) {
                        $locaisProjeto = \App\Models\LocalProjeto::where('tabfant_id', $projeto->id)
                            ->pluck('cdlocal');
                    }
                } catch (\Throwable $e) {
                    // Loga mas não quebra a busca; cai no fallback apenas por CDPROJETO
                    Log::warning('Falha ao buscar locais do projeto para filtro', [
                        'cdprojeto' => $val,
                        'erro' => $e->getMessage(),
                    ]);
                }

                $query->where(function ($q) use ($val, $locaisProjeto) {
                    $q->where('CDPROJETO', $val);
                    if ($locaisProjeto->isNotEmpty()) {
                        $q->orWhereIn('CDLOCAL', $locaisProjeto->all());
                    }
                });
            }
        }

        if ($request->filled('cdlocal')) {
            $val = trim((string) $request->input('cdlocal'));
            if ($val !== '') {
                $query->where('CDLOCAL', $val);
            }
        }

        if ($request->filled('descricao')) {
            $val = trim((string) $request->input('descricao'));
            if ($val !== '') {
                $like = '%' . mb_strtolower($val) . '%';
                // Buscar em: DEPATRIMONIO (1ª prioridade), depois MARCA, depois MODELO
                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(DEPATRIMONIO) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(MARCA) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(MODELO) LIKE ?', [$like]);
                });
            }
        }

        $this->aplicarFiltroSituacao($query, $request);
        $this->aplicarFiltroUf($query, $request);
        $this->aplicarFiltroConferido($query, $request);

        if ($request->filled('modelo')) {
            $val = trim((string) $request->input('modelo'));
            if ($val !== '') {
                $query->whereRaw('LOWER(MODELO) LIKE ?', ['%' . mb_strtolower($val) . '%']);
            }
        }

        if ($request->filled('marca')) {
            $val = trim((string) $request->input('marca'));
            if ($val !== '') {
                $query->whereRaw('LOWER(MARCA) LIKE ?', ['%' . mb_strtolower($val) . '%']);
            }
        }

        if ($request->filled('numof')) {
            $val = trim((string) $request->input('numof'));
            if ($val !== '') {
                $query->where('NUMOF', 'like', '%' . $val . '%');
            }
        }

        if ($request->filled('nmplanta')) {
            $val = trim((string) $request->input('nmplanta'));
            if ($val !== '') {
                $query->where('NMPLANTA', $val);
            }
        }

        if ($request->filled('matr_responsavel')) {
            $val = trim((string) $request->input('matr_responsavel'));
            if ($val !== '') {
                $matrValues = array_map('trim', explode(',', $val));
                $matrValues = array_filter($matrValues, function($v) { return $v !== ''; });
                
                if (count($matrValues) > 0) {
                    if (count(array_filter($matrValues, 'is_numeric')) === count($matrValues)) {
                        $query->whereIn('CDMATRFUNCIONARIO', $matrValues);
                    } else {
                        $query->where(function($q) use ($matrValues) {
                            foreach ($matrValues as $val) {
                                if (is_numeric($val)) {
                                    $q->orWhere('CDMATRFUNCIONARIO', $val);
                                } else {
                                    $usuario = User::where('NMLOGIN', $val)
                                        ->orWhereRaw('LOWER(NOMEUSER) LIKE ?', ['%' . mb_strtolower($val) . '%'])
                                        ->first();
                                    
                                    if ($usuario) {
                                        $q->orWhere('CDMATRFUNCIONARIO', $usuario->CDMATRFUNCIONARIO);
                                    } else {
                                        $q->orWhereHas('funcionario', function ($qf) use ($val) {
                                            $qf->whereRaw('LOWER(NOMEFUNCIONARIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);
                                        });
                                    }
                                }
                            }
                        });
                    }
                }
            }
        }

        $this->aplicarFiltroDataRange($query, $request, 'dtaquisicao_de', 'dtaquisicao_ate', 'DTAQUISICAO', 'filtrar_aquisicao');
        $this->aplicarFiltroDataRange($query, $request, 'dtcadastro_de', 'dtcadastro_ate', 'DTOPERACAO', 'filtrar_cadastro');
    }

    protected function aplicarOrdenacao(Builder $query, Request $request, User $user): void
    {
        $filtroCadastroAtivo = $request->boolean('filtrar_cadastro')
            || $request->filled('dtcadastro_de')
            || $request->filled('dtcadastro_ate');

        $sortableMap = [
            'nupatrimonio' => 'NUPATRIMONIO',
            'numof' => 'NUMOF',
            'codobjeto' => 'CODOBJETO',
            'nmplanta' => 'NMPLANTA',
            'nuserie' => 'NUSERIE',
            'projeto' => 'CDPROJETO',
            'local' => 'CDLOCAL',
            'modelo' => 'MODELO',
            'marca' => 'MARCA',
            'descricao' => 'DEPATRIMONIO',
            'situacao' => 'SITUACAO',
            'conferido' => 'FLCONFERIDO',
            'dtaquisicao' => 'DTAQUISICAO',
            'dtoperacao' => 'DTOPERACAO',
            'responsavel' => 'CDMATRFUNCIONARIO',
            'cadastrador' => 'USUARIO',
        ];

        $sortKey = $request->input('sort');
        $sortDirection = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $hasSort = !is_null($sortKey) && $sortKey !== '' && isset($sortableMap[$sortKey]);

        if ($hasSort) {
            // Ordenação explícita escolhida no grid
            $query->reorder();
            $query->orderBy($sortableMap[$sortKey], $sortDirection);
            return;
        }

        if ($filtroCadastroAtivo) {
            // Quando houver filtro de data de cadastro, ordenar cronologicamente pelo cadastro (mais antigo primeiro)
            $query->reorder();
            $query->orderBy('DTOPERACAO', 'asc');
        } else {
            try {
                $nmLogin = (string) ($user->NMLOGIN ?? '');
                $cdMatr = $user->CDMATRFUNCIONARIO ?? null;
                $query->orderByRaw("CASE WHEN LOWER(USUARIO) = LOWER(?) OR CDMATRFUNCIONARIO = ? THEN 0 ELSE 1 END", [$nmLogin, $cdMatr]);
                $query->orderBy('DTOPERACAO', 'desc');
            } catch (\Throwable $e) {
                Log::warning('Falha ao aplicar ordenacao por usuario/DTOPERACAO: ' . $e->getMessage());
            }
        }

        // Ordem padrÃ£o secundÃ¡ria para consistÃªncia
        $query->orderBy('DTAQUISICAO', 'asc');
    }

    protected function detectarColunasVisiveis(array $items, bool $showEmpty): array
    {
        $checks = [
            'NUMOF' => fn($p) => !blank($p->NUMOF),
            'NUSERIE' => fn($p) => !blank($p->NUSERIE),
            'MODELO' => fn($p) => !blank($p->MODELO),
            'MARCA' => fn($p) => !blank($p->MARCA),
            'NMPLANTA' => fn($p) => !blank($p->NMPLANTA),
            'CDLOCAL' => fn($p) => !blank($p->local?->cdlocal),
            'PROJETO' => fn($p) => (bool) ($p->local && $p->local->projeto),
            'DTAQUISICAO' => fn($p) => !blank($p->DTAQUISICAO),
            'DTOPERACAO' => fn($p) => !blank($p->DTOPERACAO),
            'SITUACAO' => fn($p) => !blank($p->SITUACAO),
            'CDMATRFUNCIONARIO' => fn($p) => !blank($p->CDMATRFUNCIONARIO),
            'CADASTRADOR' => fn($p) => !blank($p->USUARIO) || !blank($p->creator?->NOMEUSER),
        ];

        $visibleColumns = [];
        foreach ($checks as $key => $fn) {
            $visibleColumns[$key] = false;
            foreach ($items as $item) {
                if ($fn($item)) {
                    $visibleColumns[$key] = true;
                    break;
                }
            }
            if ($showEmpty) {
                $visibleColumns[$key] = true;
            }
        }

        $friendly = [
            'NUMOF' => 'OF',
            'NUSERIE' => 'Nº Serie',
            'MODELO' => 'Modelo',
            'MARCA' => 'Marca',
            'NMPLANTA' => 'Cod. Termo',
            'CDLOCAL' => 'Codigo Local',
            'PROJETO' => 'Projeto',
            'DTAQUISICAO' => 'Dt. Aquisição',
            'DTOPERACAO' => 'Dt. Cadastro',
            'SITUACAO' => 'Situacao',
            'CDMATRFUNCIONARIO' => 'Responsavel',
            'CADASTRADOR' => 'Cadastrador',
        ];

        $hiddenColumns = [];
        foreach ($visibleColumns as $k => $v) {
            if (!$v) {
                $hiddenColumns[] = $friendly[$k] ?? $k;
            }
        }

        return [$visibleColumns, $hiddenColumns];
    }

    /**
     * Aplica filtro de intervalo de datas usando campos do request.
     */
    protected function aplicarFiltroDataRange(Builder $query, Request $request, string $startKey, string $endKey, string $column, ?string $toggleKey = null): void
    {
        if ($toggleKey && !$request->boolean($toggleKey) && !$request->filled($startKey) && !$request->filled($endKey)) {
            return;
        }
        $inicio = $request->input($startKey);
        $fim = $request->input($endKey);

        $inicioDate = null;
        $fimDate = null;

        try {
            if (!blank($inicio)) {
                $inicioDate = Carbon::parse($inicio)->startOfDay();
            }
        } catch (\Throwable $e) {
            Log::warning("Data inicial invalida para filtro {$column}", ['valor' => $inicio, 'erro' => $e->getMessage()]);
        }

        try {
            if (!blank($fim)) {
                $fimDate = Carbon::parse($fim)->endOfDay();
            }
        } catch (\Throwable $e) {
            Log::warning("Data final invalida para filtro {$column}", ['valor' => $fim, 'erro' => $e->getMessage()]);
        }

        if ($inicioDate) {
            $query->whereDate($column, '>=', $inicioDate->toDateString());
        }

        if ($fimDate) {
            $query->whereDate($column, '<=', $fimDate->toDateString());
        }
    }

    protected function aplicarFiltroSituacao(Builder $query, Request $request): void
    {
        $raw = $request->input('situacao');
        if (is_null($raw)) {
            return;
        }

        $values = collect(is_array($raw) ? $raw : [$raw])
            ->map(fn($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->map(function ($v) {
                return $v === 'DISPONIVEL' ? 'A DISPOSICAO' : $v;
            })
            ->values();

        if ($values->isEmpty()) {
            return;
        }

        $query->whereIn('SITUACAO', $values->all());
    }

    protected function aplicarFiltroUf(Builder $query, Request $request): void
    {
        $raw = $request->input('uf_filter') ?? $request->input('uf');
        if (is_null($raw) || (is_array($raw) && empty($raw))) {
            return;
        }

        $values = collect(is_array($raw) ? $raw : [$raw])
            ->map(fn($v) => strtoupper(trim((string) $v)))
            ->filter(fn($v) => strlen($v) === 2)
            ->unique()
            ->values();

        if ($values->isEmpty()) {
            return;
        }

        // ✅ CORRIGIDO: Usar o scope byUf que filtra por:
        // 1. UF armazenada em patr.UF
        // 2. UF do local (CDLOCAL → locais_projeto.UF)
        // 3. UF do projeto (CDPROJETO → tabfant.UF)
        $query->byUf($values->all());
    }

    protected function aplicarFiltroConferido(Builder $query, Request $request): void
    {
        $raw = $request->input('conferido');
        if (is_null($raw)) {
            return;
        }

        $val = strtoupper(trim((string) $raw));
        if ($val === '') {
            return;
        }

        $truthy = ['S', '1', 'SIM', 'TRUE', 'T', 'Y', 'YES', 'ON', 'VERIFICADO', 'VERIFICADOS'];
        $falsy = ['N', '0', 'NAO', 'NÃO', 'NO', 'FALSE', 'F', 'OFF', 'NAO VERIFICADO', 'NAO VERIFICADOS', 'NÃO VERIFICADO', 'NÃO VERIFICADOS'];

        $expr = "UPPER(COALESCE(NULLIF(TRIM(FLCONFERIDO), ''), 'N'))";
        $truthyDb = "('S','1','T','Y')";

        if (in_array($val, $truthy, true)) {
            $query->whereRaw("{$expr} IN {$truthyDb}");
            return;
        }

        if (in_array($val, $falsy, true)) {
            $query->whereRaw("{$expr} NOT IN {$truthyDb}");
        }
    }
}
