<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use App\Models\HistoricoMovimentacao;
use App\Models\TermoCodigo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;

class TermoController extends Controller
{
    /**
     * Gera e salva um novo código de termo para os patrimônios selecionados.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patrimonio_ids' => 'required|array|min:1',
            'patrimonio_ids.*' => 'exists:patr,NUSEQPATR',
        ], [
            'patrimonio_ids.required' => 'Você precisa selecionar pelo menos um patrimônio.'
        ]);

        // Lógica para gerar o novo código de termo sequencial
        $ultimoCodTermo = DB::table('patr')->max('NMPLANTA');
        $novoCodTermo = $ultimoCodTermo + 1;

        // Atualiza todos os patrimônios selecionados com o novo código
        Patrimonio::whereIn('NUSEQPATR', $validated['patrimonio_ids'])
            ->update(['NMPLANTA' => $novoCodTermo]);

        // Registrar histórico de atribuição de termo para cada patrimônio
        try {
            $patrimonios = Patrimonio::whereIn('NUSEQPATR', $validated['patrimonio_ids'])->get(['NUPATRIMONIO', 'CDMATRFUNCIONARIO']);
            foreach ($patrimonios as $p) {
                $coAutor = null;
                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                $ownerMat = $p->CDMATRFUNCIONARIO;
                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                }
                HistoricoMovimentacao::create([
                    'TIPO' => 'termo',
                    'CAMPO' => 'NMPLANTA',
                    'VALOR_ANTIGO' => null,
                    'VALOR_NOVO' => $novoCodTermo,
                    'NUPATR' => $p->NUPATRIMONIO,
                    'CODPROJ' => null,
                    'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                    'CO_AUTOR' => $coAutor,
                    'DTOPERACAO' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // não interromper o fluxo por falha no histórico
        }

        // Redireciona de volta para a página de atribuição para continuidade do fluxo
        return redirect()->route('patrimonios.atribuir', ['status' => 'indisponivel'])
            ->with('success', "Código de Termo Nº {$novoCodTermo} gerado e atribuído com sucesso a " . count($validated['patrimonio_ids']) . " itens!");
    }

    /**
     * Gera a planilha Excel de um termo existente.
     */
    public function exportarExcel(Request $request)
    {
        $validated = $request->validate([
            'cod_termo' => 'required|integer',
            'cdprojeto' => 'nullable|integer',
        ]);
        $codTermo = $validated['cod_termo'];
        $query = Patrimonio::query()->where('NMPLANTA', $codTermo);
        if (!empty($validated['cdprojeto'])) {
            $query->where('CDPROJETO', $validated['cdprojeto']);
        }

        if ($query->count() === 0) {
            return back()->with('error', 'Nenhum patrimônio encontrado para o Cód. Termo informado.');
        }

        $filePath = storage_path("app/termo_de_transferencia_{$codTermo}.xlsx");
        $writer = SimpleExcelWriter::create($filePath);

        $writer->addRow(["TERMO DE TRANSFERÊNCIA Nº", $codTermo]);
        if (!empty($validated['cdprojeto'])) {
            $writer->addRow(["Projeto:", $validated['cdprojeto']]);
        }
        $writer->addRow([]);
        $writer->addRow(['Projeto Nº', 'Patr.', 'Descrição']);

        foreach ($query->cursor() as $patrimonio) {
            // Prioridade: DEPATRIMONIO -> MARCA -> fallback
            $descricao = !empty($patrimonio->DEPATRIMONIO) 
                ? $patrimonio->DEPATRIMONIO 
                : ($patrimonio->MARCA ?? 'Item sem descrição');
                
            $writer->addRow([
                'Projeto Nº' => $patrimonio->CDPROJETO,
                'Patr.' => $patrimonio->NUPATRIMONIO,
                'Descrição' => $descricao,
            ]);
        }

        $writer->addRow([]);
        $writer->addRow(['--- FIM ---']);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Lista códigos existentes (registrados e/ou usados) em JSON.
     */
    public function listarCodigos(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        // Códigos registrados manualmente
        $registrados = TermoCodigo::query()
            ->when($q !== '', fn($qq) => $qq->where('codigo', 'like', "%$q%"))
            ->get(['codigo', 'created_at'])
            ->keyBy('codigo');

        // Códigos usados na tabela patr
        $usados = Patrimonio::query()
            ->whereNotNull('NMPLANTA')
            ->when($q !== '', fn($qq) => $qq->where('NMPLANTA', 'like', "%$q%"))
            ->selectRaw('NMPLANTA as codigo, COUNT(*) as qtd')
            ->groupBy('NMPLANTA')
            ->get()
            ->keyBy('codigo');

        // União das chaves
        $codigos = collect(array_unique(array_merge(array_keys($registrados->all()), array_keys($usados->all()))))
            ->sort()
            ->values()
            ->map(function ($codigo) use ($registrados, $usados) {
                $qtd = $usados->get($codigo)->qtd ?? 0;
                $usado = $qtd > 0;
                return [
                    'codigo' => (int) $codigo,
                    'usado' => $usado,
                    'qtd' => $qtd,
                    'registrado' => $registrados->has($codigo),
                ];
            });

        return response()->json(['data' => $codigos]);
    }

    /**
     * Cria (registra) um novo código, se ainda não existir.
     */
    public function criarCodigo(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|integer|min:1',
        ]);
        $codigo = (int) $validated['codigo'];

        $jaExiste = TermoCodigo::where('codigo', $codigo)->exists()
            || Patrimonio::where('NMPLANTA', $codigo)->exists();

        if ($jaExiste) {
            return response()->json(['error' => 'Código já existe (registrado ou em uso).'], 422);
        }

        $criado = TermoCodigo::create([
            'codigo' => $codigo,
            'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
        ]);

        return response()->json(['data' => $criado], 201);
    }

    /**
     * Sugere o próximo código (max + 1) considerando registrados e usados.
     */
    public function sugestaoCodigo()
    {
        $maxRegistrado = (int) TermoCodigo::max('codigo');
        $maxUsado = (int) Patrimonio::max('NMPLANTA');
        $sugestao = max($maxRegistrado, $maxUsado) + 1;
        return response()->json(['sugestao' => $sugestao]);
    }

    /**
     * Página para gerenciar códigos (server-rendered, sem dependência de modal).
     */
    public function gerenciarCodigos(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        // Registrados
        $registrados = TermoCodigo::query()
            ->when($q !== '', fn($qq) => $qq->where('codigo', 'like', "%$q%"))
            ->get(['codigo', 'created_at'])
            ->keyBy('codigo');
        // Usados
        $usados = Patrimonio::query()
            ->whereNotNull('NMPLANTA')
            ->when($q !== '', fn($qq) => $qq->where('NMPLANTA', 'like', "%$q%"))
            ->selectRaw('NMPLANTA as codigo, COUNT(*) as qtd')
            ->groupBy('NMPLANTA')
            ->get()
            ->keyBy('codigo');
        $codigos = collect(array_unique(array_merge(array_keys($registrados->all()), array_keys($usados->all()))))
            ->sort()
            ->values()
            ->map(function ($codigo) use ($registrados, $usados) {
                $qtd = $usados->get($codigo)->qtd ?? 0;
                return [
                    'codigo' => (int) $codigo,
                    'usado' => $qtd > 0,
                    'qtd' => $qtd,
                    'registrado' => $registrados->has($codigo),
                ];
            });
        $sugestao = max((int) TermoCodigo::max('codigo'), (int) Patrimonio::max('NMPLANTA')) + 1;
        return view('termos.codigos.index', [
            'codigos' => $codigos,
            'sugestao' => $sugestao,
            'q' => $q,
        ]);
    }

    /**
     * Salva código via formulário web.
     */
    public function salvarCodigoWeb(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|integer|min:1',
        ]);
        $codigo = (int) $validated['codigo'];
        $jaExiste = TermoCodigo::where('codigo', $codigo)->exists()
            || Patrimonio::where('NMPLANTA', $codigo)->exists();
        if ($jaExiste) {
            return back()->withErrors(['codigo' => 'Código já existe (registrado ou em uso).'])->withInput();
        }
        TermoCodigo::create([
            'codigo' => $codigo,
            'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
        ]);
        return back()->with('success', 'Código cadastrado com sucesso.');
    }
}


