<?php

namespace App\Http\Controllers;

use App\Models\LocalProjeto;
use App\Models\Tabfant;
use App\Services\FilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjetoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $searchTerm = trim((string) $request->input('search', ''));
        $sortField = trim((string) $request->input('sort', ''));
        $sortDirection = trim((string) $request->input('direction', 'asc'));

        // Validar direção de ordenação
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Buscar TODOS os locais com relacionamento
        $query = LocalProjeto::with('projeto')
            ->orderBy('delocal');

        $todos_locais = $query->get()
            ->map(function ($local) {
                return [
                    'id' => $local->id,
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                    'projeto_nome' => $local->projeto?->NOMEPROJETO ?? '',
                    'projeto_codigo' => $local->projeto?->CDPROJETO ?? '',
                    '_model' => $local,  // Manter o modelo para paginar depois
                ];
            })
            ->toArray();

        // Aplicar filtro inteligente
        $filtrados = FilterService::filtrar(
            $todos_locais,
            $searchTerm,
            ['cdlocal', 'delocal', 'projeto_codigo', 'projeto_nome'],  // campos de busca
            ['cdlocal' => 'número', 'delocal' => 'texto', 'projeto_codigo' => 'número', 'projeto_nome' => 'texto'],  // tipos
            PHP_INT_MAX  // Sem limite inicial (paginação acontece depois)
        );

        // Aplicar ordenação se especificada
        if ($sortField && in_array($sortField, ['cdlocal', 'delocal', 'projeto_codigo', 'projeto_nome'])) {
            usort($filtrados, function ($a, $b) use ($sortField, $sortDirection) {
                $valorA = $a[$sortField] ?? '';
                $valorB = $b[$sortField] ?? '';

                // Para números, comparar numericamente
                if (is_numeric($valorA) && is_numeric($valorB)) {
                    $resultado = $valorA <=> $valorB;
                } else {
                    // Para textos, comparar case-insensitively
                    $valorA_lower = strtolower((string) $valorA);
                    $valorB_lower = strtolower((string) $valorB);
                    $resultado = strcmp($valorA_lower, $valorB_lower);
                }

                // Inverter resultado se descendente
                return $sortDirection === 'desc' ? -$resultado : $resultado;
            });
        }

        // Reconstruir collection com paginação
        $locais_modelo = collect(array_map(fn($f) => $f['_model'], $filtrados));

        // Paginar manualmente
        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $total = count($locais_modelo);
        $paginada = $locais_modelo->slice(($page - 1) * $perPage, $perPage);

        // Usar o paginator do Laravel
        $locais = \Illuminate\Pagination\Paginator::resolveCurrentPath()
            ? new \Illuminate\Pagination\LengthAwarePaginator(
                $paginada,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            )
            : new \Illuminate\Pagination\LengthAwarePaginator(
                $paginada,
                $total,
                $perPage,
                $page
            );

        if ($request->ajax()) {
            return view('projetos._table_partial', [
                'locais' => $locais,
                'sortField' => $sortField,
                'sortDir' => $sortDirection
            ])->render();
        }

        return view('projetos.index', [
            'locais' => $locais,
            'sortField' => $sortField,
            'sortDir' => $sortDirection
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Para popular um dropdown com os projetos disponíveis
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();
        return view('projetos.create', ['projetos' => $projetos]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id', // Valida se o projeto selecionado existe
        ]);

        LocalProjeto::create([
            'delocal' => $request->delocal,
            'cdlocal' => $request->cdlocal,
            'tabfant_id' => $request->tabfant_id,
            'flativo' => $request->boolean('flativo'),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Local cadastrado com sucesso.');
    }

    /**
     * Display the specified resource.
     * Este método pode ser usado para uma página de detalhes, se necessário.
     */
    public function show(LocalProjeto $local)
    {
        // O Laravel automaticamente encontrará o LocalProjeto pelo ID.
        return view('projetos.show', ['local' => $local]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LocalProjeto $projeto) // O Laravel vai injetar o LocalProjeto aqui
    {
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();
        return view('projetos.edit', [
            'local' => $projeto, // Renomeado para clareza
            'projetos' => $projetos
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LocalProjeto $projeto)
    {
        $request->validate([
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id',
        ]);

        $projeto->update([
            'delocal' => $request->delocal,
            'cdlocal' => $request->cdlocal,
            'tabfant_id' => $request->tabfant_id,
            'flativo' => $request->boolean('flativo'),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Local atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, LocalProjeto $projeto)
    {
        $projeto->delete();

        // Manter os filtros da requisição anterior
        $filtros = $request->only(['search', 'cdprojeto', 'local', 'tag']);

        return redirect()->route('projetos.index', $filtros)
            ->with('success', 'Local apagado com sucesso.');
    }

    /**
     * Delete multiple locals at once
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:locais_projeto,id'
        ]);

        $ids = $request->input('ids');

        try {
            LocalProjeto::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Locais removidos com sucesso.',
                'count' => count($ids)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar múltiplos locais', ['ids' => $ids, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover locais: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Abre o formulário de criação já preenchido com dados do projeto existente
     * (nome e código do projeto original) mas limpa os campos específicos do local
     * para permitir adicionar um novo local rapidamente.
     */
    public function duplicate(LocalProjeto $projeto)
    {
        // Prefill: manter o código do local e o projeto associado (tabfant_id).
        // Apenas o nome do local (delocal) deve ficar em branco e editável.
        $prefill = [
            'delocal' => '',
            'cdlocal' => $projeto->cdlocal,
            'tabfant_id' => $projeto->tabfant_id,
        ];

        // Também enviar informações do projeto original na sessão para exibição/controle na view
        $projetoOriginal = null;
        if ($projeto->tabfant_id) {
            $projetoOriginal = Tabfant::find($projeto->tabfant_id);
        }

        return redirect()
            ->route('projetos.create')
            ->withInput($prefill)
            ->with('duplicating_from', $projeto->id)
            ->with('duplicating_mode', true)
            ->with('duplicating_project', $projetoOriginal ? [
                'id' => $projetoOriginal->id,
                'CDPROJETO' => $projetoOriginal->CDPROJETO,
                'NOMEPROJETO' => $projetoOriginal->NOMEPROJETO,
            ] : null);
    }

    /**
     * Lookup AJAX: dado um cdlocal retorna se já existe local ativo e seus dados
     */
    public function lookup(Request $request)
    {
        $codigo = $request->query('cdlocal');
        if (!$codigo) {
            return response()->json(['found' => false]);
        }
        $local = LocalProjeto::with('projeto')
            ->where('cdlocal', $codigo)
            ->first(); // removido filtro estrito flativo para garantir retorno
        if (!$local) {
            return response()->json(['found' => false]);
        }
        return response()->json([
            'found' => true,
            'local' => [
                'delocal' => $local->delocal,
                'cdlocal' => $local->cdlocal,
                'tabfant_id' => $local->tabfant_id,
                'projeto_nome' => $local->projeto->NOMEPROJETO ?? null,
                'projeto_codigo' => $local->projeto->CDPROJETO ?? null,
            ]
        ]);
    }

    /**
     * 🆕 Criar novo local de forma simplificada (POST + Redirect igual duplicate)
     * Recebe: cdlocal, delocal, tabfant_id
     * Redireciona para patrimonios/create com dados preenchidos
     */
    public function criarSimples(Request $request)
    {
        $validated = $request->validate([
            'cdlocal' => 'required|integer',
            'delocal' => 'required|string|max:255',
            'tabfant_id' => 'required|exists:tabfant,id',
        ]);

        try {
            $novoLocal = LocalProjeto::create([
                'cdlocal' => $validated['cdlocal'],
                'delocal' => $validated['delocal'],
                'tabfant_id' => $validated['tabfant_id'],
                'flativo' => true,
            ]);

            Log::info('✅ Novo local criado', [
                'id' => $novoLocal->id,
                'cdlocal' => $novoLocal->cdlocal,
                'delocal' => $novoLocal->delocal,
            ]);

            // Buscar informações do projeto
            $projeto = Tabfant::find($validated['tabfant_id']);

            // Redirecionar para create com dados preenchidos (igual duplicate)
            return redirect()
                ->route('patrimonios.create')
                ->withInput([
                    'codigo_local_digitado' => $validated['cdlocal'],
                    'local_id_selecionado' => $novoLocal->id,
                ])
                ->with('success', 'Local "' . $novoLocal->delocal . '" criado com sucesso!')
                ->with('local_criado', $novoLocal->id)
                ->with('projeto_info', $projeto ? [
                    'id' => $projeto->id,
                    'codigo' => $projeto->CDPROJETO,
                    'nome' => $projeto->NOMEPROJETO,
                ] : null);
        } catch (\Exception $e) {
            Log::error('❌ Erro ao criar local', [
                'message' => $e->getMessage(),
                'payload' => $validated,
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao criar local: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
