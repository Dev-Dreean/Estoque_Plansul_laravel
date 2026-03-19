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
            $telasCadastradas = DB::table('acessotela')->get();
            $nomeNormalizado = $this->normalizarNomeTela($nome);

            $exists = $telasCadastradas->first(function ($tela) use ($nomeNormalizado) {
                return $this->normalizarNomeTela((string) $tela->DETELA) === $nomeNormalizado;
            });

            if ($exists) {
                $inserted = false;
                return;
            }

            $telaPrincipal = $this->localizarTelaPrincipalPorNome($nome, $this->getTelasPrincipais());
            $codigoPreferencial = $telaPrincipal['codigo'] ?? null;

            if ($codigoPreferencial !== null) {
                $codigoOcupado = $telasCadastradas->firstWhere('NUSEQTELA', $codigoPreferencial);
                if ($codigoOcupado) {
                    $inserted = false;
                    return;
                }
            }

            $sugestaoCodigo = $codigoPreferencial !== null
                ? (int) $codigoPreferencial
                : $this->proximoCodigoLivre($telasCadastradas);

            DB::table('acessotela')->insert([
                'NUSEQTELA' => $sugestaoCodigo,
                'DETELA' => $nome,
                'NMSISTEMA' => $telaPrincipal ? 'Sistema Principal' : 'Plansul',
                'FLACESSO' => 'S',
            ]);

            $inserted = $sugestaoCodigo;
        });

        if ($inserted === false) {
            return redirect()->route('cadastro-tela.index')->with('warning', "A tela \"{$nome}\" já está cadastrada ou já possui um código principal em uso.");
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
            $telasAtuais = collect($cadastradas->all());

            foreach ($telasPrincipais as $tela) {
                $nomeNormalizado = $this->normalizarNomeTela((string) $tela['nome']);
                $exists = $telasAtuais->first(function ($item) use ($nomeNormalizado, $tela) {
                    return (int) $item->NUSEQTELA === (int) $tela['codigo']
                        || $this->normalizarNomeTela((string) $item->DETELA) === $nomeNormalizado;
                });

                if ($exists) {
                    continue;
                }

                $codigo = $telasAtuais->contains(fn ($item) => (int) $item->NUSEQTELA === (int) $tela['codigo'])
                    ? $this->proximoCodigoLivre($telasAtuais)
                    : (int) $tela['codigo'];

                DB::table('acessotela')->insert([
                    'NUSEQTELA' => $codigo,
                    'DETELA' => $tela['nome'],
                    'NMSISTEMA' => 'Sistema Principal',
                    'FLACESSO' => 'S',
                ]);

                $inseridas++;
                $telasAtuais->push((object) [
                    'NUSEQTELA' => $codigo,
                    'DETELA' => $tela['nome'],
                ]);
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
            ['codigo' => 1010, 'nome' => 'Solicitações de Bens', 'rota' => '/solicitacoes-bens'],
            ['codigo' => 1016, 'nome' => 'Histórico de Solicitações', 'rota' => '/solicitacoes-bens/historico'],
            ['codigo' => 1017, 'nome' => 'Solicitações - Gerenciar Visibilidade', 'rota' => '/solicitacoes-bens'],
            ['codigo' => 1018, 'nome' => 'Solicitações - Visualização Restrita', 'rota' => '/solicitacoes-bens'],
            ['codigo' => 1019, 'nome' => 'Solicitações - Triagem Inicial', 'rota' => '/solicitacoes-bens'],
            ['codigo' => 1020, 'nome' => 'Solicitações - Liberação de Envio', 'rota' => '/solicitacoes-bens'],
        ];
    }

    private function localizarTelaPrincipalPorNome(string $nome, array $telasPrincipais): ?array
    {
        $nomeNormalizado = $this->normalizarNomeTela($nome);

        $matchExato = collect($telasPrincipais)->first(function ($tela) use ($nomeNormalizado) {
            return $this->normalizarNomeTela((string) $tela['nome']) === $nomeNormalizado;
        });

        if ($matchExato) {
            return $matchExato;
        }

        return collect($telasPrincipais)
            ->filter(function ($tela) use ($nomeNormalizado) {
                $nomeTela = $this->normalizarNomeTela((string) $tela['nome']);
                return str_contains($nomeTela, $nomeNormalizado) || str_contains($nomeNormalizado, $nomeTela);
            })
            ->sortByDesc(fn ($tela) => mb_strlen((string) $tela['nome']))
            ->first();
    }

    private function proximoCodigoLivre($telas): int
    {
        $usados = collect($telas)
            ->pluck('NUSEQTELA')
            ->map(static fn ($codigo) => (int) $codigo)
            ->unique()
            ->all();

        $codigo = empty($usados) ? 1000 : (max($usados) + 1);
        while (in_array($codigo, $usados, true)) {
            $codigo++;
        }

        return $codigo;
    }

    private function normalizarNomeTela(string $nome): string
    {
        $nome = trim($nome);
        $nome = preg_replace('/\s+/u', ' ', $nome) ?: $nome;

        return mb_strtoupper($nome, 'UTF-8');
    }
}
