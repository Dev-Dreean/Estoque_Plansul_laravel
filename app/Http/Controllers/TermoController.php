<?php

namespace App\Http\Controllers;

use App\Models\HistoricoMovimentacao;
use App\Models\Patrimonio;
use App\Models\TermoCodigo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'patrimonio_ids.required' => 'Você precisa selecionar pelo menos um patrimônio.',
        ]);

        $ultimoCodTermo = (int) DB::table('patr')->max('NMPLANTA');
        $novoCodTermo = $ultimoCodTermo + 1;

        try {
            $dadosTermo = [
                'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA',
            ];

            if (TermoCodigo::hasTituloColumn()) {
                $dadosTermo['titulo'] = null;
            }

            TermoCodigo::updateOrCreate(
                ['codigo' => $novoCodTermo],
                $dadosTermo
            );
        } catch (\Throwable $e) {
            Log::warning('Não foi possível sincronizar os metadados do termo durante a geração.', [
                'codigo' => $novoCodTermo,
                'erro' => $e->getMessage(),
            ]);
        }

        Patrimonio::whereIn('NUSEQPATR', $validated['patrimonio_ids'])
            ->update(['NMPLANTA' => $novoCodTermo]);

        try {
            $patrimonios = Patrimonio::whereIn('NUSEQPATR', $validated['patrimonio_ids'])
                ->get(['NUPATRIMONIO', 'CDMATRFUNCIONARIO']);

            foreach ($patrimonios as $patrimonio) {
                $coAutor = null;
                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                $ownerMat = $patrimonio->CDMATRFUNCIONARIO;

                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                }

                HistoricoMovimentacao::create([
                    'TIPO' => 'termo',
                    'CAMPO' => 'NMPLANTA',
                    'VALOR_ANTIGO' => null,
                    'VALOR_NOVO' => $novoCodTermo,
                    'NUPATR' => $patrimonio->NUPATRIMONIO,
                    'CODPROJ' => null,
                    'USUARIO' => Auth::user()->NMLOGIN ?? 'SISTEMA',
                    'CO_AUTOR' => $coAutor,
                    'DTOPERACAO' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar o histórico de geração do termo.', [
                'codigo' => $novoCodTermo,
                'erro' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('patrimonios.atribuir', ['status' => 'indisponivel'])
            ->with('success', "Código de Termo Nº {$novoCodTermo} gerado e atribuído com sucesso a " . count($validated['patrimonio_ids']) . ' itens!');
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

        $codTermo = (int) $validated['cod_termo'];
        $query = Patrimonio::query()->where('NMPLANTA', $codTermo);

        if (!empty($validated['cdprojeto'])) {
            $query->where('CDPROJETO', $validated['cdprojeto']);
        }

        if ($query->count() === 0) {
            return back()->with('error', 'Nenhum patrimônio encontrado para o Cód. Termo informado.');
        }

        $filePath = storage_path("app/termo_de_transferencia_{$codTermo}.xlsx");
        $writer = SimpleExcelWriter::create($filePath);

        $writer->addRow(['TERMO DE TRANSFERÊNCIA Nº', $codTermo]);
        if (!empty($validated['cdprojeto'])) {
            $writer->addRow(['Projeto:', $validated['cdprojeto']]);
        }
        $writer->addRow([]);
        $writer->addRow(['Projeto Nº', 'Patr.', 'Descrição']);

        foreach ($query->cursor() as $patrimonio) {
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
     * Lista códigos existentes em JSON.
     */
    public function listarCodigos(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        $registrados = TermoCodigo::query()
            ->when($q !== '', fn ($query) => $query->where('codigo', 'like', "%{$q}%"))
            ->get(['codigo', 'created_at'])
            ->keyBy('codigo');

        $usados = Patrimonio::query()
            ->whereNotNull('NMPLANTA')
            ->when($q !== '', fn ($query) => $query->where('NMPLANTA', 'like', "%{$q}%"))
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

        return response()->json(['data' => $codigos]);
    }

    /**
     * Cria um novo código, se ainda não existir.
     */
    public function criarCodigo(Request $request): JsonResponse
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
            'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA',
        ]);

        return response()->json(['data' => $criado], 201);
    }

    /**
     * Sugere o próximo código considerando registrados e usados.
     */
    public function sugestaoCodigo(): JsonResponse
    {
        $maxRegistrado = (int) TermoCodigo::max('codigo');
        $maxUsado = (int) Patrimonio::max('NMPLANTA');
        $sugestao = max($maxRegistrado, $maxUsado) + 1;

        return response()->json(['sugestao' => $sugestao]);
    }

    public function atualizarTitulo(Request $request, int $codigo): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:120'],
        ]);

        $codigo = (int) $codigo;
        if ($codigo <= 0) {
            return response()->json(['message' => 'Código do termo inválido.'], 422);
        }

        if (!Patrimonio::where('NMPLANTA', $codigo)->exists()) {
            return response()->json(['message' => 'Esse termo não está em uso no momento.'], 404);
        }

        if (!TermoCodigo::hasTituloColumn()) {
            return response()->json(['message' => 'A edição do nome do agrupado ainda não está disponível neste ambiente. Execute a migration pendente.'], 409);
        }

        $registro = TermoCodigo::firstOrCreate(
            ['codigo' => $codigo],
            ['created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA']
        );

        $usuario = Auth::user();
        $loginAtual = strtoupper(trim((string) ($usuario->NMLOGIN ?? '')));
        $criador = strtoupper(trim((string) ($registro->created_by ?? '')));
        $usuariosDoTermo = Patrimonio::query()
            ->where('NMPLANTA', $codigo)
            ->pluck('USUARIO')
            ->filter(fn ($login) => trim((string) $login) !== '')
            ->map(fn ($login) => strtoupper(trim((string) $login)))
            ->unique()
            ->values();
        $criadorInferido = $usuariosDoTermo->count() === 1 ? (string) $usuariosDoTermo->first() : '';
        $podeEditar = ($usuario && ($usuario->isGod() || $usuario->isAdmin()))
            || ($loginAtual !== '' && $criador !== '' && $loginAtual === $criador)
            || ($loginAtual !== '' && $criadorInferido !== '' && $loginAtual === $criadorInferido);

        if (!$podeEditar) {
            return response()->json(['message' => 'Apenas quem gerou o termo pode editar o nome deste agrupado.'], 403);
        }

        if ($criador === '' && $criadorInferido !== '') {
            TermoCodigo::query()
                ->where('codigo', $codigo)
                ->update(['created_by' => $criadorInferido]);
        }

        $titulo = trim((string) ($validated['titulo'] ?? ''));

        TermoCodigo::query()
            ->where('codigo', $codigo)
            ->update([
                'titulo' => $titulo !== '' ? $titulo : null,
            ]);

        return response()->json([
            'message' => $titulo !== ''
                ? 'Nome do agrupado atualizado com sucesso.'
                : 'Nome personalizado removido com sucesso.',
            'data' => [
                'codigo' => $codigo,
                'titulo' => $titulo !== '' ? $titulo : null,
            ],
        ]);
    }

    /**
     * Página para gerenciar códigos.
     */
    public function gerenciarCodigos(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $registrados = TermoCodigo::query()
            ->when($q !== '', fn ($query) => $query->where('codigo', 'like', "%{$q}%"))
            ->get(['codigo', 'created_at'])
            ->keyBy('codigo');

        $usados = Patrimonio::query()
            ->whereNotNull('NMPLANTA')
            ->when($q !== '', fn ($query) => $query->where('NMPLANTA', 'like', "%{$q}%"))
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
            'created_by' => Auth::user()->NMLOGIN ?? 'SISTEMA',
        ]);

        return back()->with('success', 'Código cadastrado com sucesso.');
    }
}
