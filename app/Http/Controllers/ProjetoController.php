<?php

namespace App\Http\Controllers;

use App\Models\LocalProjeto;
use App\Models\Tabfant;
use App\Services\FilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProjetoController extends Controller
{
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

        $query = LocalProjeto::with('projeto')->orderBy('delocal');

        $todosLocais = $query->get()
            ->map(function (LocalProjeto $local): array {
                return [
                    'id' => $local->id,
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                    'tipo_local' => $local->tipo_local_normalizado,
                    'tipo_local_label' => $local->tipo_local_label,
                    'fluxo_responsavel' => $local->fluxo_responsavel_normalizado,
                    'fluxo_responsavel_label' => $local->fluxo_responsavel_label,
                    'projeto_nome' => $local->projeto?->NOMEPROJETO ?? '',
                    'projeto_codigo' => $local->projeto?->CDPROJETO ?? '',
                    '_model' => $local,
                ];
            })
            ->toArray();

        $filtrados = FilterService::filtrar(
            $todosLocais,
            $searchTerm,
            ['cdlocal', 'delocal', 'tipo_local_label', 'fluxo_responsavel_label', 'projeto_codigo', 'projeto_nome'],
            [
                'cdlocal' => 'número',
                'delocal' => 'texto',
                'tipo_local_label' => 'texto',
                'fluxo_responsavel_label' => 'texto',
                'projeto_codigo' => 'número',
                'projeto_nome' => 'texto',
            ],
            PHP_INT_MAX
        );

        $sortableColumns = ['cdlocal', 'delocal', 'tipo_local_label', 'fluxo_responsavel_label', 'projeto_nome', 'projeto_codigo'];
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

        $locais = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginada,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

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

    public function create()
    {
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();

        return view('projetos.create', ['projetos' => $projetos]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());

        $nomeUppercase = strtoupper((string) $request->delocal);
        $localExistente = LocalProjeto::whereRaw('UPPER(delocal) = ?', [$nomeUppercase])->first();

        if ($localExistente) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Já existe um local com o nome '{$nomeUppercase}'. Por favor, escolha outro nome.");
        }

        LocalProjeto::create($this->payloadFromRequest($request));

        return redirect()->route('projetos.index')->with('success', 'Local cadastrado com sucesso.');
    }

    public function show(LocalProjeto $local)
    {
        return view('projetos.show', ['local' => $local]);
    }

    public function edit(LocalProjeto $projeto)
    {
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();

        return view('projetos.edit', [
            'local' => $projeto,
            'projetos' => $projetos,
        ]);
    }

    public function update(Request $request, LocalProjeto $projeto)
    {
        $request->validate($this->rules());

        $nomeUppercase = strtoupper((string) $request->delocal);
        $localExistente = LocalProjeto::whereRaw('UPPER(delocal) = ?', [$nomeUppercase])
            ->where('id', '!=', $projeto->id)
            ->first();

        if ($localExistente) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Já existe outro local com o nome '{$nomeUppercase}'. Por favor, escolha outro nome.");
        }

        $projeto->update($this->payloadFromRequest($request));

        return redirect()->route('projetos.index')->with('success', 'Local atualizado com sucesso.');
    }

    public function destroy(Request $request, LocalProjeto $projeto)
    {
        $projeto->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Local apagado com sucesso.'], 200);
        }

        $filtros = $request->only(['search', 'cdprojeto', 'local', 'tag']);

        return redirect()->route('projetos.index', $filtros)
            ->with('success', 'Local apagado com sucesso.');
    }

    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:locais_projeto,id',
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
                'count' => $deletedCount,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar múltiplos locais', ['ids' => $ids, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover locais: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function duplicate(LocalProjeto $projeto)
    {
        $prefill = [
            'delocal' => '',
            'cdlocal' => $projeto->cdlocal,
            'tabfant_id' => $projeto->tabfant_id,
            'tipo_local' => $projeto->tipo_local_normalizado,
            'fluxo_responsavel' => $projeto->fluxo_responsavel_normalizado,
        ];

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

    public function lookup(Request $request)
    {
        $projetoId = $request->query('projeto_id');
        $codigo = $request->query('cdlocal');

        if ($projetoId) {
            $locais = LocalProjeto::where('tabfant_id', $projetoId)
                ->orderBy('delocal')
                ->get()
                ->map(fn (LocalProjeto $local) => [
                    'id' => $local->id,
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                    'tabfant_id' => $local->tabfant_id,
                    'tipo_local' => $local->tipo_local_normalizado,
                    'tipo_local_label' => $local->tipo_local_label,
                    'fluxo_responsavel' => $local->fluxo_responsavel_normalizado,
                    'fluxo_responsavel_label' => $local->fluxo_responsavel_label,
                ])
                ->toArray();

            return response()->json($locais);
        }

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
                    'tipo_local' => $local->tipo_local_normalizado,
                    'tipo_local_label' => $local->tipo_local_label,
                    'fluxo_responsavel' => $local->fluxo_responsavel_normalizado,
                    'fluxo_responsavel_label' => $local->fluxo_responsavel_label,
                    'projeto_nome' => $local->projeto->NOMEPROJETO ?? null,
                    'projeto_codigo' => $local->projeto->CDPROJETO ?? null,
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }

    public function criarSimples(Request $request)
    {
        $validated = $request->validate([
            'cdlocal' => 'required|integer',
            'delocal' => 'required|string|max:255',
            'tabfant_id' => 'required|exists:tabfant,id',
            'tipo_local' => ['nullable', Rule::in(array_keys(LocalProjeto::tipoLocalOptions()))],
            'fluxo_responsavel' => ['nullable', Rule::in(array_keys(LocalProjeto::fluxoResponsavelOptions()))],
        ]);

        try {
            $novoLocal = LocalProjeto::create([
                'cdlocal' => $validated['cdlocal'],
                'delocal' => $validated['delocal'],
                'tabfant_id' => $validated['tabfant_id'],
                'flativo' => true,
                'tipo_local' => $this->normalizarTipoLocalInput($validated['tipo_local'] ?? null),
                'fluxo_responsavel' => $this->normalizarFluxoResponsavelInput($validated['fluxo_responsavel'] ?? null),
            ]);

            Log::info('Novo local criado', [
                'id' => $novoLocal->id,
                'cdlocal' => $novoLocal->cdlocal,
                'delocal' => $novoLocal->delocal,
            ]);

            $projeto = Tabfant::find($validated['tabfant_id']);

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
            Log::error('Erro ao criar local', [
                'message' => $e->getMessage(),
                'payload' => $validated,
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Erro ao criar local: ' . $e->getMessage()])
                ->withInput();
        }
    }

    private function rules(): array
    {
        return [
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id',
            'tipo_local' => ['nullable', Rule::in(array_keys(LocalProjeto::tipoLocalOptions()))],
            'fluxo_responsavel' => ['nullable', Rule::in(array_keys(LocalProjeto::fluxoResponsavelOptions()))],
        ];
    }

    private function payloadFromRequest(Request $request): array
    {
        return [
            'delocal' => $request->input('delocal'),
            'cdlocal' => $request->input('cdlocal'),
            'tabfant_id' => $request->input('tabfant_id'),
            'flativo' => $request->boolean('flativo'),
            'tipo_local' => $this->normalizarTipoLocalInput($request->input('tipo_local')),
            'fluxo_responsavel' => $this->normalizarFluxoResponsavelInput($request->input('fluxo_responsavel')),
        ];
    }

    private function normalizarTipoLocalInput(mixed $value): string
    {
        $tipo = mb_strtoupper(trim((string) $value), 'UTF-8');

        return array_key_exists($tipo, LocalProjeto::tipoLocalOptions())
            ? $tipo
            : LocalProjeto::TIPO_LOCAL_PADRAO;
    }

    private function normalizarFluxoResponsavelInput(mixed $value): string
    {
        $fluxo = mb_strtoupper(trim((string) $value), 'UTF-8');

        return array_key_exists($fluxo, LocalProjeto::fluxoResponsavelOptions())
            ? $fluxo
            : LocalProjeto::FLUXO_RESPONSAVEL_PADRAO;
    }
}
