<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $statusMode = $this->resolveStatusMode($request);
        $cadastroBase = Patrimonio::query();
        $this->applyStatusMode($cadastroBase, $statusMode);

        $cadastrosHoje = (clone $cadastroBase)->whereDate('DTOPERACAO', Carbon::today())->count();
        $verificadosStats = null;
        $user = Auth::user();
        if ($this->canViewVerificadosIndicator($user)) {
            $baseQuery = Patrimonio::query();
            $this->applyStatusMode($baseQuery, $statusMode);
            $total = (clone $baseQuery)->count();
            $verificados = (clone $baseQuery)
                ->whereRaw("UPPER(COALESCE(NULLIF(TRIM(FLCONFERIDO), ''), 'N')) = 'S'")
                ->count();
            $percent = $total > 0 ? (int) round(($verificados / $total) * 100) : 0;
            $verificadosStats = [
                'total' => $total,
                'verificados' => $verificados,
                'percent' => $percent,
            ];
        }

        // Busca os 5 usuarios que mais cadastraram (Top 5) - cache de 5 minutos
        $topCadastradores = Cache::remember("dashboard_top_cadastradores_{$statusMode}", 300, function () use ($statusMode) {
            return Patrimonio::query()
                ->join('usuario', 'patr.CDMATRFUNCIONARIO', '=', 'usuario.CDMATRFUNCIONARIO')
                ->select('usuario.NOMEUSER', DB::raw('count(patr.NUSEQPATR) as total'))
                ->groupBy('usuario.NOMEUSER')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->tap(fn($q) => $this->applyStatusMode($q, $statusMode, 'patr'))
                ->get();
        });

        $cadastrosSemanaLabels = [];
        $cadastrosSemanaData = [];
        // Construir apenas os dias que tiveram movimentacao (> 0)
        for ($i = 6; $i >= 0; $i--) {
            $data = Carbon::today()->subDays($i);
            $count = (clone $cadastroBase)->whereDate('DTOPERACAO', $data)->count();
            if ($count > 0) {
                $cadastrosSemanaLabels[] = $data->format('d/m');
                $cadastrosSemanaData[] = $count;
            }
        }

        return view('dashboard', [
            'cadastrosHoje' => $cadastrosHoje,
            'topCadastradores' => $topCadastradores,
            'cadastrosSemanaLabels' => $cadastrosSemanaLabels,
            'cadastrosSemanaData' => $cadastrosSemanaData,
            'verificadosStats' => $verificadosStats,
            'statusMode' => $statusMode,
        ]);
    }

    /**
     * Retorna JSON com labels e data para o grafico conforme o periodo solicitado.
     * Parametros aceitos via query: period = day|week|month|year (default: week)
     * Cache de 5 minutos por period+statusMode para evitar 7-30 queries COUNT por request.
     */
    public function data(Request $request)
    {
        $period = $request->query('period', 'week');
        $statusMode = $this->resolveStatusMode($request);

        $cacheKey = "dashboard_data_{$period}_{$statusMode}";
        $dados = Cache::remember($cacheKey, 300, function () use ($period, $statusMode) {
            $labels = [];
            $values = [];
            $baseQuery = Patrimonio::query();
            $this->applyStatusMode($baseQuery, $statusMode);

        if ($period === 'day') {
            // ultimas 24 horas por hora
            for ($h = 0; $h < 24; $h++) {
                $start = Carbon::today()->addHours($h);
                $end = (clone $start)->addHour();
                $count = (clone $baseQuery)->whereBetween('DTOPERACAO', [$start, $end])->count();
                if ($count > 0) {
                    $labels[] = $start->format('H:00');
                    $values[] = $count;
                }
            }
        } elseif ($period === 'month') {
            // ultimos 30 dias
            for ($i = 29; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i);
                $count = (clone $baseQuery)->whereDate('DTOPERACAO', $d)->count();
                if ($count > 0) {
                    $labels[] = $d->format('d/m');
                    $values[] = $count;
                }
            }
        } elseif ($period === 'year') {
            // ultimos 12 meses
            for ($m = 11; $m >= 0; $m--) {
                $d = Carbon::now()->subMonths($m)->startOfMonth();
                $count = (clone $baseQuery)->whereBetween('DTOPERACAO', [$d, (clone $d)->endOfMonth()])->count();
                if ($count > 0) {
                    $labels[] = $d->format('M/Y');
                    $values[] = $count;
                }
            }
        } else {
            // semana (default) - ultimos 7 dias
            for ($i = 6; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i);
                $count = (clone $baseQuery)->whereDate('DTOPERACAO', $d)->count();
                if ($count > 0) {
                    $labels[] = $d->format('d/m');
                    $values[] = $count;
                }
            }
        }

            return ['labels' => $labels, 'values' => $values];
        });

        return response()->json([
            'labels' => $dados['labels'],
            'data' => $dados['values'],
            'period' => $period,
            'status_mode' => $statusMode,
        ]);
    }

    /**
     * Retorna JSON com contagem de lançamentos agrupados por UF (estado).
     * Cache de 10 minutos para evitar queries pesadas com subselects.
     */
    public function ufData(Request $request)
    {
        $statusMode = $this->resolveStatusMode($request);

        $dados = Cache::remember("dashboard_uf_data_{$statusMode}", 600, function () use ($statusMode) {
            // Resolve UF sem JOIN para evitar duplicidade quando CDLOCAL aparece em mais de um projeto.
            // Prioridade: patr.UF -> locais_projeto.UF -> tabfant.UF
            $ufExpr = "UPPER(TRIM(COALESCE(
                NULLIF(TRIM(patr.UF), ''),
                (SELECT MAX(NULLIF(TRIM(lp.UF), '')) FROM locais_projeto lp WHERE lp.cdlocal = patr.CDLOCAL),
                (SELECT MAX(NULLIF(TRIM(tf.UF), '')) FROM tabfant tf WHERE tf.CDPROJETO = patr.CDPROJETO)
            )))";

            $resolvedUfQuery = DB::table('patr')->selectRaw("$ufExpr as uf");
            $this->applyStatusMode($resolvedUfQuery, $statusMode, 'patr');

            $rows = DB::query()
                ->fromSub($resolvedUfQuery, 'patr_uf')
                ->selectRaw('uf, COUNT(*) as total')
                ->groupBy('uf')
                ->orderByDesc('total')
                ->get();

            $labels = [];
            $values = [];
            $semUf = 0;

            foreach ($rows as $row) {
                $uf = isset($row->uf) ? trim((string) $row->uf) : '';
                $total = (int) ($row->total ?? 0);

                if ($total <= 0) {
                    continue;
                }

                if ($uf === '') {
                    $semUf += $total;
                    continue;
                }

                $labels[] = $uf;
                $values[] = $total;
            }

            if ($semUf > 0) {
                $labels[] = 'SEM UF';
                $values[] = $semUf;
            }

            return ['labels' => $labels, 'data' => $values];
        });

        return response()->json([
            'labels' => $dados['labels'],
            'data' => $dados['data'],
            'period' => 'all',
            'status_mode' => $statusMode,
        ]);
    }

    /**
     * Retorna totais gerais (verificados x não verificados) no período.
     */
    public function totalData(Request $request)
    {
        $statusMode = $this->resolveStatusMode($request);
        $query = DB::table('patr');
        $this->applyStatusMode($query, $statusMode, 'patr');

        $expr = "UPPER(COALESCE(NULLIF(TRIM(FLCONFERIDO), ''), 'N'))";
        $row = $query->selectRaw(
            "SUM(CASE WHEN {$expr} = 'S' THEN 1 ELSE 0 END) as verificados,
             SUM(CASE WHEN {$expr} <> 'S' THEN 1 ELSE 0 END) as nao_verificados"
        )->first();

        $verificados = (int) ($row->verificados ?? 0);
        $naoVerificados = (int) ($row->nao_verificados ?? 0);

        $labels = ['Verificados', 'Não verificados'];
        $values = [$verificados, $naoVerificados];

        return response()->json([
            'labels' => $labels,
            'data' => $values,
            'period' => 'all',
            'status_mode' => $statusMode,
        ]);
    }

    private function resolveStatusMode(Request $request): string
    {
        $raw = strtolower(trim((string) $request->query('status_mode', 'ativos')));
        $map = [
            'ativos' => 'ativos',
            'all' => 'all',
            'incluir_baixados' => 'all',
            'baixa' => 'baixa',
            'apenas_baixados' => 'baixa',
            'em_uso' => 'em_uso',
            'a_disposicao' => 'a_disposicao',
            'conserto' => 'conserto',
        ];

        return $map[$raw] ?? 'ativos';
    }

    private function applyStatusMode($query, string $mode, ?string $table = null): void
    {
        if ($mode === 'all') {
            return;
        }

        $prefix = $table ? $table . '.' : '';
        $colSituacao = $prefix . 'SITUACAO';
        $colCdSituacao = $prefix . 'CDSITUACAO';
        $colDtBaixa = $prefix . 'DTBAIXA';
        $hasSituacao = $this->hasPatrColumn('SITUACAO');
        $hasCdSituacao = $this->hasPatrColumn('CDSITUACAO');
        $hasDtBaixa = $this->hasPatrColumn('DTBAIXA');

        if ($mode === 'ativos') {
            $this->applyExcludeBaixa($query, $table);
            return;
        }

        if ($mode === 'baixa') {
            $query->where(function ($q) use ($colSituacao, $colCdSituacao, $colDtBaixa, $hasSituacao, $hasCdSituacao, $hasDtBaixa) {
                $applied = false;

                if ($hasCdSituacao) {
                    $q->where($colCdSituacao, 2);
                    $applied = true;
                }

                if ($hasSituacao) {
                    if ($applied) {
                        $q->orWhereRaw("UPPER(TRIM({$colSituacao})) LIKE '%BAIXA%'");
                    } else {
                        $q->whereRaw("UPPER(TRIM({$colSituacao})) LIKE '%BAIXA%'");
                        $applied = true;
                    }
                }

                if ($hasDtBaixa) {
                    if ($applied) {
                        $q->orWhereNotNull($colDtBaixa);
                    } else {
                        $q->whereNotNull($colDtBaixa);
                        $applied = true;
                    }
                }

                if (!$applied) {
                    $q->whereRaw('1 = 0');
                }
            });
            return;
        }

        if ($mode === 'em_uso') {
            if ($hasSituacao) {
                $query->whereRaw("UPPER(TRIM({$colSituacao})) LIKE '%EM USO%'");
                return;
            }

            if ($hasCdSituacao) {
                $query->where($colCdSituacao, 1);
                return;
            }

            $query->whereRaw('1 = 0');
            return;
        }

        if ($mode === 'a_disposicao') {
            if ($hasSituacao) {
                $query->where(function ($q) use ($colSituacao) {
                    $q->whereRaw("UPPER(TRIM({$colSituacao})) LIKE '%DISPOS%'")
                        ->orWhereRaw("UPPER(TRIM({$colSituacao})) LIKE '%DISPONIVEL%'");
                });
                return;
            }

            $query->whereRaw('1 = 0');
            return;
        }

        if ($mode === 'conserto') {
            if ($hasSituacao) {
                $query->where(function ($q) use ($colSituacao) {
                    $q->whereRaw("UPPER(TRIM({$colSituacao})) LIKE '%CONSERTO%'")
                        ->orWhereRaw("UPPER(TRIM({$colSituacao})) LIKE '%MANUTEN%'");
                });
                return;
            }

            if ($hasCdSituacao) {
                $query->where($colCdSituacao, 3);
                return;
            }

            $query->whereRaw('1 = 0');
        }
    }

    private function canViewVerificadosIndicator($user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        $allowlist = array_filter(array_map('trim', explode(',', (string) env('DASHBOARD_VERIFICADOS_LOGINS', ''))));
        if (!$allowlist) {
            return false;
        }

        $login = strtolower((string) ($user->NMLOGIN ?? $user->NOMEUSER ?? ''));
        return $login !== '' && in_array($login, array_map('strtolower', $allowlist), true);
    }

    private function applyExcludeBaixa($query, ?string $table = null): void
    {
        $prefix = $table ? $table . '.' : '';
        $colSituacao = $prefix . 'SITUACAO';
        $colCdSituacao = $prefix . 'CDSITUACAO';
        $colDtBaixa = $prefix . 'DTBAIXA';
        $hasSituacao = $this->hasPatrColumn('SITUACAO');
        $hasCdSituacao = $this->hasPatrColumn('CDSITUACAO');
        $hasDtBaixa = $this->hasPatrColumn('DTBAIXA');

        if (!$hasSituacao && !$hasCdSituacao && !$hasDtBaixa) {
            return;
        }

        $query->where(function ($q) use ($colSituacao, $colCdSituacao, $colDtBaixa, $hasSituacao, $hasCdSituacao, $hasDtBaixa) {
            if ($hasCdSituacao) {
                $q->where(function ($s) use ($colCdSituacao) {
                    $s->whereNull($colCdSituacao)
                        ->orWhere($colCdSituacao, '<>', 2);
                });
            }

            if ($hasSituacao) {
                $q->where(function ($s) use ($colSituacao) {
                    $s->whereNull($colSituacao)
                        ->orWhereRaw("UPPER(TRIM({$colSituacao})) NOT LIKE '%BAIXA%'");
                });
            }

            if ($hasDtBaixa) {
                $q->whereNull($colDtBaixa);
            }
        });
    }

    private function hasPatrColumn(string $column): bool
    {
        static $columns = null;
        $key = strtoupper(trim($column));

        if ($columns === null) {
            $columns = [];
            try {
                // SELECT costuma funcionar mesmo quando SHOW COLUMNS e bloqueado no host.
                $row = DB::table('patr')->select('*')->limit(1)->first();
                if ($row) {
                    foreach (array_keys(get_object_vars($row)) as $fieldName) {
                        $field = strtoupper(trim((string) $fieldName));
                        if ($field !== '') {
                            $columns[$field] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fallback abaixo
            }

            if ($columns === []) {
                try {
                    $result = DB::select('SHOW COLUMNS FROM patr');
                    foreach ($result as $row) {
                        $field = strtoupper(trim((string) ($row->Field ?? '')));
                        if ($field !== '') {
                            $columns[$field] = true;
                        }
                    }
                } catch (\Throwable $e) {
                    $columns = [];
                }
            }
        }

        return isset($columns[$key]);
    }
}

