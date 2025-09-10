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
        for ($i = 6; $i >= 0; $i--) {
            $data = Carbon::today()->subDays($i);
            $cadastrosSemanaLabels[] = $data->format('d/m');
            $cadastrosSemanaData[] = Patrimonio::whereDate('DTOPERACAO', $data)->count();
        }

        return view('dashboard', [
            'cadastrosHoje' => $cadastrosHoje,
            'topCadastradores' => $topCadastradores,
            'cadastrosSemanaLabels' => json_encode($cadastrosSemanaLabels),
            'cadastrosSemanaData' => json_encode($cadastrosSemanaData),
        ]);
    }
}