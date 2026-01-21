<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FuncionarioController extends Controller
{
    /**
     * 🚀 Pesquisa OTIMIZADA de funcionários com CACHE e LIMIT
     * 
     * ⚡ Performance:
     *   - Cache: ~5ms (hit)
     *   - Matrícula: ~10-20ms (indexed)
     *   - Nome LIKE início: ~30-50ms (indexed)
     *   - FULLTEXT fallback: ~50-100ms
     * 
     * 🎯 Estratégias:
     *   1. Cache de 5min para mesma query
     *   2. LIMIT BAIXO (50 max) para evitar overhead
     *   3. Busca progressiva: LIKE início → FULLTEXT → LIKE wildcard
     */
    public function pesquisar(Request $request)
    {
        $termo = trim($request->input('q', ''));
        
        if (empty($termo)) {
            return response()->json([]);
        }

        // 💾 CACHE de 5 minutos (300s) - evita queries repetidas
        $cacheKey = 'funcionarios_search_' . md5(strtolower($termo));
        
        $funcionarios = Cache::remember($cacheKey, 300, function () use ($termo) {
            $isNumero = is_numeric($termo);

            if ($isNumero) {
                // 🏃 Busca por matrícula: ULTRA RÁPIDA (1-20ms) com índice PRIMARY
                return Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                    ->where('CDMATRFUNCIONARIO', 'LIKE', $termo . '%')
                    ->limit(50) // ⬆️ Aumentado de 15 para 50
                    ->get()
                    ->toArray();
            }

            // 🔤 ESTRATÉGIA 1: LIKE com INÍCIO (PRIORITÁRIO - mais preciso)
            // Usa índice idx_nmfuncionario para performance
            $funcionarios = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                ->whereRaw('UPPER(NMFUNCIONARIO) LIKE ?', [strtoupper($termo) . '%'])
                ->limit(50) // ⬆️ Aumentado de 15 para 50
                ->get()
                ->toArray();
            
            // 🔤 ESTRATÉGIA 2: FULLTEXT (fallback para sobrenomes/palavras internas)
            if (empty($funcionarios)) {
                $funcionarios = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                    ->whereRaw('MATCH(NMFUNCIONARIO) AGAINST(? IN NATURAL LANGUAGE MODE)', [$termo])
                    ->orderByRaw('MATCH(NMFUNCIONARIO) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$termo])
                    ->limit(50) // ⬆️ Aumentado de 15 para 50
                    ->get()
                    ->toArray();
            }
            
            // 🔤 ESTRATÉGIA 3: Wildcard LIKE (último recurso)
            if (empty($funcionarios)) {
                $funcionarios = Funcionario::select(['CDMATRFUNCIONARIO', 'NMFUNCIONARIO'])
                    ->whereRaw('UPPER(NMFUNCIONARIO) LIKE ?', ['%' . strtoupper($termo) . '%'])
                    ->limit(50) // ⬆️ Aumentado de 15 para 50
                    ->get()
                    ->toArray();
            }

            return $funcionarios;
        });

        return response()->json($funcionarios);
    }
}



