<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CadastroTelaController extends Controller
{
    public function index()
    {
        // Apenas Super Admin
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isGod()) {
            abort(403, 'Acesso não autorizado.');
        }

        $telasCadastradas = DB::table('acessotela')->orderBy('NUSEQTELA')->get();
        $telasPrincipais = $this->getTelasPrincipais();

        // Monta grid unificada: começa com itens do banco
        $grid = [];
        foreach ($telasCadastradas as $t) {
            $grid[] = [
                'DETELA' => $t->DETELA,
                'rota' => null,
                'NUSEQTELA' => $t->NUSEQTELA,
                'NMSISTEMA' => $t->NMSISTEMA,
                'FLACESSO' => $t->FLACESSO,
                'cadastrada' => true,
            ];
        }

        // Para cada tela principal que não exista no banco, adiciona como não vinculada
        foreach ($telasPrincipais as $tp) {
            $found = false;
            foreach ($telasCadastradas as $t) {
                // Verifica por código (preferencial) ou por nome
                if (
                    $t->NUSEQTELA == $tp['codigo'] ||
                    stripos($t->DETELA, $tp['nome']) !== false ||
                    stripos($tp['nome'], $t->DETELA) !== false
                ) {
                    // atualiza rota na entrada existente, se necessário
                    foreach ($grid as &$g) {
                        if ($g['NUSEQTELA'] === $t->NUSEQTELA) {
                            $g['rota'] = $tp['rota'];
                        }
                    }
                    unset($g);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $grid[] = [
                    'DETELA' => $tp['nome'],
                    'rota' => $tp['rota'],
                    'NUSEQTELA' => $tp['codigo'], // usa o código sugerido
                    'NMSISTEMA' => 'Sistema Principal',
                    'FLACESSO' => null,
                    'cadastrada' => false,
                ];
            }
        }

        return view('cadastro-tela', [
            'telasGrid' => $grid,
        ]);
    }

    public function store(Request $request)
    {
        // Apenas Super Admin
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isGod()) {
            abort(403, 'Acesso não autorizado');
        }

        $request->validate([
            'NUSEQTELA' => 'required|integer|unique:acessotela,NUSEQTELA',
            'DETELA' => 'required|string|max:100',
            'NMSISTEMA' => 'required|string|max:60',
        ]);

        $data = $request->only(['NUSEQTELA', 'DETELA', 'NMSISTEMA']);
        $data['FLACESSO'] = 'S';
        try {
            DB::table('acessotela')->insert($data);
        } catch (\Exception $e) {
            return redirect()->route('cadastro-tela.index')->with('error', 'Falha ao salvar a tela: ' . $e->getMessage());
        }

        // Limpa sugestão de formulário após inserir
        $request->session()->forget('formTela');

        return redirect()->route('cadastro-tela.index')->with('success', 'Tela cadastrada com sucesso!');
    }

    public function showForm(Request $request, $nome)
    {
        // Apenas Super Admin
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isGod()) {
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

    public function gerarVincular(Request $request, $nome)
    {
        // Apenas Super Admin
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isGod()) {
            abort(403, 'Acesso não autorizado');
        }

        $inserted = null;
        DB::transaction(function () use ($nome, &$inserted) {
            $exists = DB::table('acessotela')->get()->first(function ($t) use ($nome) {
                return stripos($t->DETELA, $nome) !== false;
            });
            if ($exists) {
                $inserted = false;
                return;
            }

            // Busca código sugerido nas telas principais
            $telasPrincipais = $this->getTelasPrincipais();
            $telaPrincipal = collect($telasPrincipais)->first(function ($tp) use ($nome) {
                return stripos($tp['nome'], $nome) !== false || stripos($nome, $tp['nome']) !== false;
            });

            // Usa código sugerido ou gera próximo sequencial
            $sug = $telaPrincipal ? $telaPrincipal['codigo'] : (DB::table('acessotela')->max('NUSEQTELA') + 1 ?: 1000);

            DB::table('acessotela')->insert([
                'NUSEQTELA' => $sug,
                'DETELA' => $nome,
                'NMSISTEMA' => $telaPrincipal ? 'Sistema Principal' : 'Plansul',
                'FLACESSO' => 'S',
            ]);
            $inserted = $sug;
        });

        if ($inserted === false) {
            return redirect()->route('cadastro-tela.index')->with('warning', "A tela \"{$nome}\" já está cadastrada.");
        }

        return redirect()->route('cadastro-tela.index')->with('success', "Tela \"{$nome}\" vinculada com código {$inserted}.");
    }

    public function vincularTodas(Request $request)
    {
        // Apenas Super Admin
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
                $exists = $cadastradas->first(function ($t) use ($nome) {
                    return stripos($t->DETELA, $nome) !== false;
                });
                if ($exists) continue;
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

    private function getTelasPrincipais()
    {
        return [
            ['codigo' => 1000, 'nome' => 'Controle de Patrimônio', 'rota' => '/patrimonios'],
            ['codigo' => 1001, 'nome' => 'Dashboard - Gráficos', 'rota' => '/dashboard'],
            ['codigo' => 1002, 'nome' => 'Cadastro de Locais', 'rota' => '/projetos'],
            ['codigo' => 1003, 'nome' => 'Cadastro de Usuários', 'rota' => '/usuarios'],
            ['codigo' => 1004, 'nome' => 'Cadastro de Telas', 'rota' => '/cadastro-tela'],
            ['codigo' => 1005, 'nome' => 'Gerenciar Acessos', 'rota' => '/acessos'],
            ['codigo' => 1006, 'nome' => 'Relatórios', 'rota' => '/relatorios'],
            ['codigo' => 1007, 'nome' => 'Histórico de Movimentações', 'rota' => '/historico'],
            ['codigo' => 1008, 'nome' => 'Configurações de Tema', 'rota' => '/settings/theme'],
        ];
    }
}
