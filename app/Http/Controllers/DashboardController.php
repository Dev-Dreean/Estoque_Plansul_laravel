<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $cadastroBase = Patrimonio::query();
        $this->applyExcludeBaixa($cadastroBase);

        $cadastrosHoje = (clone $cadastroBase)->whereDate('DTOPERACAO', Carbon::today())->count();
        $verificadosStats = null;
        $user = Auth::user();
        if ($this->canViewVerificadosIndicator($user)) {
            $baseQuery = Patrimonio::query();
            $this->applyExcludeBaixa($baseQuery);
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

        // Busca os 5 usuarios que mais cadastraram (Top 5)
        $topCadastradores = Patrimonio::query()
            ->join('usuario', 'patr.CDMATRFUNCIONARIO', '=', 'usuario.CDMATRFUNCIONARIO')
            ->select('usuario.NOMEUSER', DB::raw('count(patr.NUSEQPATR) as total'))
            ->groupBy('usuario.NOMEUSER')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->tap(fn($q) => $this->applyExcludeBaixa($q, 'patr'))
            ->get();

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
        ]);
    }

    /**
     * Retorna JSON com labels e data para o grafico conforme o periodo solicitado.
     * Parametros aceitos via query: period = day|week|month|year (default: week)
     */
    public function data(Request $request)
    {
        $period = $request->query('period', 'week');

        $labels = [];
        $values = [];
        $baseQuery = Patrimonio::query();
        $this->applyExcludeBaixa($baseQuery);

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

        return response()->json([
            'labels' => $labels,
            'data' => $values,
            'period' => $period,
        ]);
    }

    /**
     * Retorna JSON com contagem de lancamentos agrupados por UF (estado).
     * Obs: este grafico e "geral" (sem filtro por periodo).
     */
    public function ufData(Request $request)
    {
        // Prioridade: patr.UF (se preenchido) -> locais_projeto.UF -> tabfant.UF
        $ufExpr = "UPPER(TRIM(COALESCE(NULLIF(patr.UF,''), NULLIF(locais_projeto.UF,''), NULLIF(tabfant.UF,''))))";

        $rowsQuery = DB::table('patr')
            ->leftJoin('locais_projeto', 'locais_projeto.cdlocal', '=', 'patr.CDLOCAL')
            ->leftJoin('tabfant', 'tabfant.CDPROJETO', '=', 'patr.CDPROJETO')
            ->selectRaw("$ufExpr as uf, COUNT(*) as total")
            ->groupBy('uf')
            ->orderBy('total', 'desc');
        $this->applyExcludeBaixa($rowsQuery, 'patr');
        $rows = $rowsQuery->get();

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

        return response()->json([
            'labels' => $labels,
            'data' => $values,
            'period' => 'all',
        ]);
    }

    /**
     * Retorna totais gerais (verificados x nao verificados) no periodo.
     */
    public function totalData(Request $request)
    {
        $query = DB::table('patr');
        $this->applyExcludeBaixa($query, 'patr');

        $expr = "UPPER(COALESCE(NULLIF(TRIM(FLCONFERIDO), ''), 'N'))";
        $row = $query->selectRaw(
            "SUM(CASE WHEN {$expr} = 'S' THEN 1 ELSE 0 END) as verificados,
             SUM(CASE WHEN {$expr} <> 'S' THEN 1 ELSE 0 END) as nao_verificados"
        )->first();

        $verificados = (int) ($row->verificados ?? 0);
        $naoVerificados = (int) ($row->nao_verificados ?? 0);

        $labels = ['Verificados', 'Nao verificados'];
        $values = [$verificados, $naoVerificados];

        return response()->json([
            'labels' => $labels,
            'data' => $values,
            'period' => 'all',
        ]);
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

        try {
            if (Schema::hasColumn('patr', 'CDSITUACAO')) {
                $query->where(function ($q) use ($colCdSituacao) {
                    $q->whereNull($colCdSituacao)
                        ->orWhere($colCdSituacao, '<>', 2);
                });
                return;
            }
        } catch (\Exception $e) {
            // MySQL antigo não suporta generation_expression, pular CDSITUACAO
        }

        try {
            if (Schema::hasColumn('patr', 'SITUACAO')) {
                $query->where(function ($q) use ($colSituacao) {
                    $q->whereNull($colSituacao)
                        ->orWhereRaw("UPPER(TRIM({$colSituacao})) NOT LIKE '%BAIXA%'");
                });
            }
        } catch (\Exception $e) {
            // MySQL antigo não suporta generation_expression, pular SITUACAO
        }
    }
}
