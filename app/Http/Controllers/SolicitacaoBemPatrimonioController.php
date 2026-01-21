<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitacaoBemPatrimonioController extends Controller
{
    /**
     * Buscar patrimonios disponiveis para solicitacao.
     * Retorna apenas itens em estoque disponivel.
     */
    public function buscarDisponivel(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $availableStatuses = [
            'DISPONIVEL',
            'DISPONÍVEL',
            'A DISPOSICAO',
            'A DISPOSIÇÃO',
            'À DISPOSIÇÃO',
        ];

        $query = Patrimonio::query()
            ->whereIn('SITUACAO', $availableStatuses);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('NUPATRIMONIO', 'like', "%$search%")
                    ->orWhere('DEPATRIMONIO', 'like', "%$search%");
            });
        }

        $patrimonios = $query->select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'FLCONFERIDO', 'PESO'])
            ->orderBy('NUPATRIMONIO')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->NUSEQPATR,
                'nupatrimonio' => $p->NUPATRIMONIO,
                'descricao' => $p->DEPATRIMONIO,
                'conferido' => $p->FLCONFERIDO === 'S',
                'peso' => $p->PESO ? (float) $p->PESO : null,
                'text' => "{$p->NUPATRIMONIO} - {$p->DEPATRIMONIO}",
            ]);

        return response()->json($patrimonios);
    }
}


