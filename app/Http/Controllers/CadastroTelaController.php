<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CadastroTelaController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->temAcessoTela(1004)) {
            abort(403, 'Acesso não autorizado');
        }

        $telasCadastradas = DB::table('acessotela')->orderBy('NUSEQTELA')->get();
        $telasPrincipais = $this->getTelasPrincipais();

        // Grid inicial: registros já cadastrados na tabela de acesso.
        $grid = [];
        foreach ($telasCadastradas as $tela) {
            $grid[] = [
                'DETELA' => $tela->DETELA,
                'rota' => null,
                'NUSEQTELA' => $tela->NUSEQTELA,
                'NMSISTEMA' => $tela->NMSISTEMA,
                'FLACESSO' => $tela->FLACESSO,
                'cadastrada' => true,
            ];
        }

        // Adiciona telas principais ainda não cadastradas.
        foreach ($telasPrincipais as $telaPrincipal) {
            $found = false;

            foreach ($telasCadastradas as $telaCadastrada) {
                if (
                    $telaCadastrada->NUSEQTELA == $telaPrincipal['codigo'] ||
                    stripos($telaCadastrada->DETELA, $telaPrincipal['nome']) !== false ||
                    stripos($telaPrincipal['nome'], $telaCadastrada->DETELA) !== false
                ) {
                    foreach ($grid as &$item) {
                        if ($item['NUSEQTELA'] === $telaCadastrada->NUSEQTELA) {
                            $item['rota'] = $telaPrincipal['rota'];
                        }
                    }
                    unset($item);

                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $grid[] = [
                    'DETELA' => $telaPrincipal['nome'],
                    'rota' => $telaPrincipal['rota'],
                    'NUSEQTELA' => $telaPrincipal['codigo'],
                    'NMSISTEMA' => 'Sistema Principal',
                    'FLACESSO' => null,
                    'cadastrada' => false,
                ];
            }
        }

        $searchInput = $request->input('search', $request->input('busca', ''));
        $searchTerms = [];
        if (is_array($searchInput)) {
            $searchTerms = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $searchInput)));
        } else {
            $single = trim((string) $searchInput);
            if ($single !== '') {
                $searchTerms = preg_split('/[\s,|]+/', $single, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        if (!empty($searchTerms)) {
            $grid = array_values(array_filter($grid, function (array $row) use ($searchTerms): bool {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($row['DETELA'] ?? ''),
                    (string) ($row['rota'] ?? ''),
                    (string) ($row['NUSEQTELA'] ?? ''),
                    (string) ($row['NMSISTEMA'] ?? ''),
                    ($row['cadastrada'] ?? false) ? 'cadastrada' : 'não vinculada nao vinculada',
                ]));

                foreach ($searchTerms as $term) {
                    $needle = mb_strtolower((string) $term);
                    if ($needle === '') {
                        continue;
                    }
                    if (!str_contains($haystack, $needle)) {
                        return false;
                    }
                }

                return true;
            }));
        }

        $sortableColumns = ['DETELA', 'rota', 'NUSEQTELA', 'NMSISTEMA', 'cadastrada'];
        $sort = (string) $request->input('sort', 'DETELA');
        if (!in_array($sort, $sortableColumns, true)) {
            $sort = 'DETELA';
        }

        $direction = strtolower((string) $request->input('direction', 'asc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        usort($grid, function (array $a, array $b) use ($sort, $direction): int {
            $av = $a[$sort] ?? '';
            $bv = $b[$sort] ?? '';

            if ($sort === 'NUSEQTELA') {
                $cmp = ((int) $av) <=> ((int) $bv);
            } elseif ($sort === 'cadastrada') {
                $cmp = ((int) (bool) $av) <=> ((int) (bool) $bv);
            } else {
                $cmp = strcasecmp((string) $av, (string) $bv);
            }

            if ($cmp === 0) {
                $cmp = strcasecmp((string) ($a['DETELA'] ?? ''), (string) ($b['DETELA'] ?? ''));
            }

            return $direction === 'desc' ? -$cmp : $cmp;
        });

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;
        $total = count($grid);
        $pageItems = collect($grid)->slice(($page - 1) * $perPage, $perPage)->values();

        $telasGrid = new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        if ($request->has('api') && $request->input('api') === '1') {
            $html = view('telas._table_rows', ['telasGrid' => $telasGrid])->render();
            return response()->json(['html' => $html]);
        }

        if ($request->ajax()) {
            return view('telas._table_partial', [
                'telasGrid' => $telasGrid,
                'sort' => $sort,
                'direction' => $direction,
            ])->render();
        }

        return view('cadastro-tela', [
            'telasGrid' => $telasGrid,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->temAcessoTela(1004)) {
            abort(403, 'Acesso não autorizado');
        }

        $request->validate(
            [
                'NUSEQTELA' => 'required|integer|unique:acessotela,NUSEQTELA',
                'DETELA' => 'required|string|max:100',
                'NMSISTEMA' => 'required|string|max:60',
            ],
            [],
            [
                'NUSEQTELA' => 'Código da tela',
                'DETELA' => 'Nome da tela',
                'NMSISTEMA' => 'Sistema',
            ]
        );

        $data = $request->only(['NUSEQTELA', 'DETELA', 'NMSISTEMA']);
        $data['FLACESSO'] = 'S';

        try {
            DB::table('acessotela')->insert($data);
        } catch (\Exception $e) {
            return redirect()->route('cadastro-tela.index')->with('error', 'Falha ao salvar a tela: ' . $e->getMessage());
        }

        $request->session()->forget('formTela');

        return redirect()->route('cadastro-tela.index')->with('success', 'Tela cadastrada com sucesso!');
    }

    public function showForm(Request $request, string $nome)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->temAcessoTela(1004)) {
            abort(403, 'Acesso não autorizado');
        }

        $max = DB::table('acessotela')->max('NUSEQTELA');
        $sugestao = $max ? ($max + 1) : 1000;

        $request->session()->put('formTela', [
            'NUSEQTELA' => $sugestao,
            'DETELA' => $nome,
            'NMSISTEMA' => 'Plansul',
        ]);

        return redirect()->route('cadastro-tela.index');
    }

    public function gerarVincular(Request $request, string $nome)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->temAcessoTela(1004)) {
            abort(403, 'Acesso não autorizado');
        }

        $inserted = null;

        DB::transaction(function () use ($nome, &$inserted) {
            $exists = DB::table('acessotela')->get()->first(function ($tela) use ($nome) {
                return stripos($tela->DETELA, $nome) !== false;
            });

            if ($exists) {
                $inserted = false;
                return;
            }

            $telasPrincipais = $this->getTelasPrincipais();
            $telaPrincipal = collect($telasPrincipais)->first(function ($tela) use ($nome) {
                return stripos($tela['nome'], $nome) !== false || stripos($nome, $tela['nome']) !== false;
            });

            $sugestaoCodigo = $telaPrincipal
                ? $telaPrincipal['codigo']
                : (DB::table('acessotela')->max('NUSEQTELA') + 1 ?: 1000);

            DB::table('acessotela')->insert([
                'NUSEQTELA' => $sugestaoCodigo,
                'DETELA' => $nome,
                'NMSISTEMA' => $telaPrincipal ? 'Sistema Principal' : 'Plansul',
                'FLACESSO' => 'S',
            ]);

            $inserted = $sugestaoCodigo;
        });

        if ($inserted === false) {
            return redirect()->route('cadastro-tela.index')->with('warning', "A tela \"{$nome}\" já está cadastrada.");
        }

        return redirect()->route('cadastro-tela.index')->with('success', "Tela \"{$nome}\" vinculada com código {$inserted}.");
    }

    public function vincularTodas(Request $request)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isGod()) {
            abort(403, 'Acesso não autorizado');
        }

        $telasPrincipais = $this->getTelasPrincipais();
        $cadastradas = DB::table('acessotela')->get();
        $inseridas = 0;

        DB::transaction(function () use ($telasPrincipais, $cadastradas, &$inseridas) {
            $max = DB::table('acessotela')->max('NUSEQTELA');
            $next = $max ? ($max + 1) : 1000;

            foreach ($telasPrincipais as $tela) {
                $nome = $tela['nome'];
                $exists = $cadastradas->first(function ($item) use ($nome) {
                    return stripos($item->DETELA, $nome) !== false;
                });

                if ($exists) {
                    continue;
                }

                DB::table('acessotela')->insert([
                    'NUSEQTELA' => $next,
                    'DETELA' => $nome,
                    'NMSISTEMA' => 'Plansul',
                    'FLACESSO' => 'S',
                ]);

                $inseridas++;
                $next++;
            }
        });

        if ($inseridas === 0) {
            return redirect()->route('cadastro-tela.index')->with('info', 'Nenhuma tela nova para vincular.');
        }

        return redirect()->route('cadastro-tela.index')->with('success', "{$inseridas} telas vinculadas com sucesso.");
    }

    private function getTelasPrincipais(): array
    {
        return [
            ['codigo' => 1000, 'nome' => 'Controle de Patrimônio', 'rota' => '/patrimonios'],
            ['codigo' => 1001, 'nome' => 'Dashboard - Gráficos', 'rota' => '/dashboard'],
            ['codigo' => 1002, 'nome' => 'Cadastro de Locais', 'rota' => '/projetos'],
            ['codigo' => 1003, 'nome' => 'Cadastro de Usuários', 'rota' => '/usuarios'],
            ['codigo' => 1004, 'nome' => 'Cadastro de Telas', 'rota' => '/cadastro-tela'],
            ['codigo' => 1006, 'nome' => 'Relatórios', 'rota' => '/relatorios'],
            ['codigo' => 1007, 'nome' => 'Histórico', 'rota' => '/historico'],
            ['codigo' => 1008, 'nome' => 'Configurações de Tema', 'rota' => '/settings/theme'],
            ['codigo' => 1010, 'nome' => 'Solicitações de Bens', 'rota' => '/solicitacoes-bens'],
        ];
    }
}
