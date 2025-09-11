<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $cadastrosHoje = Patrimonio::whereDate('DTOPERACAO', Carbon::today())->count();

        // Busca os 5 usuários que mais cadastraram (Top 5)
        $topCadastradores = Patrimonio::query()
            ->join('usuario', 'patr.CDMATRFUNCIONARIO', '=', 'usuario.CDMATRFUNCIONARIO')
            ->select('usuario.NOMEUSER', DB::raw('count(patr.NUSEQPATR) as total'))
            ->groupBy('usuario.NOMEUSER')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        $cadastrosSemanaLabels = [];
        $cadastrosSemanaData = [];
        // Construir apenas os dias que tiveram movimentação (> 0)
        for ($i = 6; $i >= 0; $i--) {
            $data = Carbon::today()->subDays($i);
            $count = Patrimonio::whereDate('DTOPERACAO', $data)->count();
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
        ]);
    }

    /**
     * Retorna JSON com labels e data para o gráfico conforme o período solicitado.
     * Parâmetros aceitos via query: period = day|week|month|year (default: week)
     */
    public function data(Request $request)
    {
        $period = $request->query('period', 'week');

        $labels = [];
        $values = [];

        if ($period === 'day') {
            // últimas 24 horas por hora
            for ($h = 0; $h < 24; $h++) {
                $start = Carbon::today()->addHours($h);
                $end = (clone $start)->addHour();
                $count = Patrimonio::whereBetween('DTOPERACAO', [$start, $end])->count();
                if ($count > 0) {
                    $labels[] = $start->format('H:00');
                    $values[] = $count;
                }
            }
        } elseif ($period === 'month') {
            // últimos 30 dias
            for ($i = 29; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i);
                $count = Patrimonio::whereDate('DTOPERACAO', $d)->count();
                if ($count > 0) {
                    $labels[] = $d->format('d/m');
                    $values[] = $count;
                }
            }
        } elseif ($period === 'year') {
            // últimos 12 meses
            for ($m = 11; $m >= 0; $m--) {
                $d = Carbon::now()->subMonths($m)->startOfMonth();
                $count = Patrimonio::whereBetween('DTOPERACAO', [$d, (clone $d)->endOfMonth()])->count();
                if ($count > 0) {
                    $labels[] = $d->format('M/Y');
                    $values[] = $count;
                }
            }
        } else {
            // semana (default) - últimos 7 dias
            for ($i = 6; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i);
                $count = Patrimonio::whereDate('DTOPERACAO', $d)->count();
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
}
