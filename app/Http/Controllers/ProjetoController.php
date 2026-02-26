<?php

namespace App\Http\Controllers;

use App\Models\LocalProjeto;
use App\Models\Tabfant;
use App\Services\FilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjetoController extends Controller
{
    /**
     * Listar os locais/projetos.
     */
    public function index(Request $request)
    {
        $searchInput = $request->input('search', $request->input('busca', ''));
        $searchTerms = [];
        if (is_array($searchInput)) {
            $searchTerms = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $searchInput)));
        } else {
            $single = trim((string) $searchInput);
            if ($single !== '') {
                $searchTerms = preg_split('/[\s,|]+/', $single, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }
        $searchTerm = implode(' ', $searchTerms);

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

        // Paginar manualmente - MANTER OS DADOS MAPEADOS, NÃO DESCARTAR!
        $sortableColumns = ['cdlocal', 'delocal', 'projeto_nome', 'projeto_codigo'];
        $sort = strtolower((string) $request->input('sort', 'delocal'));
        if (!in_array($sort, $sortableColumns, true)) {
            $sort = 'delocal';
        }
        $direction = strtolower((string) $request->input('direction', 'asc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        usort($filtrados, function (array $a, array $b) use ($sort, $direction): int {
            $av = $a[$sort] ?? '';
            $bv = $b[$sort] ?? '';
            $isNumericSort = in_array($sort, ['cdlocal', 'projeto_codigo'], true);

            if ($isNumericSort) {
                $cmp = ((int) $av) <=> ((int) $bv);
            } else {
                $cmp = strcasecmp((string) $av, (string) $bv);
            }

            if ($cmp === 0) {
                $cmp = strcasecmp((string) ($a['delocal'] ?? ''), (string) ($b['delocal'] ?? ''));
            }

            return $direction === 'desc' ? -$cmp : $cmp;
        });

        $page = (int) $request->input('page', 1);
        $perPage = 30;
        $total = count($filtrados);
        $paginada = collect($filtrados)->slice(($page - 1) * $perPage, $perPage)->values()->toArray();

        // Usar o paginator do Laravel - PASSAR OS DADOS MAPEADOS
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

        // Se requisição é AJAX com api=1, retorna JSON com HTML das linhas
        if ($request->has('api') && $request->input('api') === '1') {
            $html = view('projetos._table_rows', ['locais' => $locais])->render();
            return response()->json(['html' => $html]);
        }

        if ($request->ajax()) {
            return view('projetos._table_partial', [
                'locais' => $locais,
                'sort' => $sort,
                'direction' => $direction,
            ])->render();
        }

        return view('projetos.index', [
            'locais' => $locais,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Exibir formulário de criação de local.
     */
    public function create()
    {
        // Para popular um dropdown com os projetos disponíveis
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();
        return view('projetos.create', ['projetos' => $projetos]);
    }

    /**
     * Armazenar novo registro de local.
     */
    public function store(Request $request)
    {
        $request->validate([
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id', // Valida se o projeto selecionado existe
        ]);

        // Verificar se já existe um local com o mesmo nome (uppercase)
        $nomeUppercase = strtoupper($request->delocal);
        $localExistente = LocalProjeto::whereRaw('UPPER(delocal) = ?', [$nomeUppercase])->first();

        if ($localExistente) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Já existe um local com o nome '{$nomeUppercase}'. Por favor, escolha outro nome.");
        }

        LocalProjeto::create([
            'delocal' => $request->delocal,
            'cdlocal' => $request->cdlocal,
            'tabfant_id' => $request->tabfant_id,
            'flativo' => $request->boolean('flativo'),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Local cadastrado com sucesso.');
    }

    /**
     * Exibir o local especificado.
     * Este método pode ser usado para uma página de detalhes, se necessário.
     */
    public function show(LocalProjeto $local)
    {
        // O Laravel automaticamente encontrará o LocalProjeto pelo ID.
        return view('projetos.show', ['local' => $local]);
    }

    /**
     * Exibir formulário de edição do local.
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
     * Atualizar o registro do local.
     */
    public function update(Request $request, LocalProjeto $projeto)
    {
        $request->validate([
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id',
        ]);

        // Verificar se já existe outro local com o mesmo nome (uppercase), excluindo o atual
        $nomeUppercase = strtoupper($request->delocal);
        $localExistente = LocalProjeto::whereRaw('UPPER(delocal) = ?', [$nomeUppercase])
            ->where('id', '!=', $projeto->id)
            ->first();

        if ($localExistente) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Já existe outro local com o nome '{$nomeUppercase}'. Por favor, escolha outro nome.");
        }

        $projeto->update([
            'delocal' => $request->delocal,
            'cdlocal' => $request->cdlocal,
            'tabfant_id' => $request->tabfant_id,
            'flativo' => $request->boolean('flativo'),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Local atualizado com sucesso.');
    }

    /**
     * Remover o local do armazenamento.
     */
    public function destroy(Request $request, LocalProjeto $projeto)
    {
        $projeto->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Local apagado com sucesso.'], 200);
        }

        // Manter os filtros da requisição anterior
        $filtros = $request->only(['search', 'cdprojeto', 'local', 'tag']);

        return redirect()->route('projetos.index', $filtros)
            ->with('success', 'Local apagado com sucesso.');
    }

    /**
     * Deletar múltiplos locais de uma vez.
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:locais_projeto,id'
        ]);

        $ids = $request->input('ids');

        try {
            $locais = LocalProjeto::whereIn('id', $ids)->get();
            $deletedCount = 0;

            DB::transaction(function () use ($locais, &$deletedCount) {
                foreach ($locais as $local) {
                    if ($local->delete()) {
                        $deletedCount++;
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Locais removidos com sucesso.',
                'count' => $deletedCount
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
    /**
     * 🔍 Buscar locais por projeto (para formulário de solicitações de bens)
     * Parametro: projeto_id (ID do projeto)
     * Retorna: Array de locais do projeto
     */
    public function lookup(Request $request)
    {
        $projetoId = $request->query('projeto_id');
        $codigo = $request->query('cdlocal');
        
        // Se for busca por projeto_id (para cascading no formulário de solicitações)
        if ($projetoId) {
            $locais = LocalProjeto::where('tabfant_id', $projetoId)
                ->orderBy('delocal')
                ->get()
                ->map(fn($local) => [
                    'id' => $local->id,
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                    'tabfant_id' => $local->tabfant_id,
                ])
                ->toArray();
            
            return response()->json($locais);
        }
        
        // Se for busca por código do local (compatibilidade com uso anterior)
        if ($codigo) {
            $local = LocalProjeto::with('projeto')
                ->where('cdlocal', $codigo)
                ->first();
            
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
        
        return response()->json(['found' => false]);
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
