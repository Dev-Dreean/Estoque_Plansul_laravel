<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitacaoBemPatrimonioController extends Controller
{
    /**
     * Buscar patrimônios disponíveis para solicitação
     * Retorna apenas itens com status "disponível" e não baixados/em manutenção
     */
    public function buscarDisponivel(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $query = Patrimonio::query()
            ->where('SITUACAO', '=', 'disponível')
            ->where(function ($q) use ($search) {
                if ($search) {
                    $q->where('NUPATRIMONIO', 'like', "%$search%")
                        ->orWhere('DEPATRIMONIO', 'like', "%$search%");
                }
            })
            ->select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'FLCONFERIDO'])
            ->orderBy('NUPATRIMONIO')
            ->limit(50);

        $patrimonios = $query->get()
            ->map(fn ($p) => [
                'id' => $p->NUSEQPATR,
                'nupatrimonio' => $p->NUPATRIMONIO,
                'descricao' => $p->DEPATRIMONIO,
                'conferido' => $p->FLCONFERIDO === 'S',
                'text' => "{$p->NUPATRIMONIO} - {$p->DEPATRIMONIO}",
            ]);

        return response()->json($patrimonios);
    }
}
