<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Funcionario;
use App\Models\ObjetoPatr;
use App\Models\Patrimonio;
use App\Models\Tabfant;
use App\Models\TermoResponsabilidadeArquivo;
use App\Models\TermoResponsabilidadeArquivoItem;
use App\Services\PatrimonioLocalResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

class TermoResponsabilidadeController extends Controller
{
    private const MAX_FUNCIONARIOS = 500;
    private const TEMPLATE_REFERENCIA_PATH = 'storage/app/templates/termo_responsabilidade_itens.docx';

    public function gerarPdfEmMassa(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', Patrimonio::class);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $validated = $request->validate([
            'cdprojeto' => ['required', 'string', 'max:20'],
        ], [
            'cdprojeto.required' => 'Informe o c?digo do projeto para gerar os termos.',
            'cdprojeto.max' => 'O c?digo do projeto informado ? inv?lido.',
        ]);

        try {
            $cdProjeto = trim((string) $validated['cdprojeto']);
            $projeto = $this->buscarProjeto($cdProjeto);

            if (!$projeto) {
                return $this->responderErroLote($request, 'Projeto n?o encontrado para gerar os termos em lote.', 404);
            }

            $patrimonios = $this->buscarPatrimoniosPorProjeto($cdProjeto);

            if ($patrimonios->isEmpty()) {
                return $this->responderErroLote($request, 'Nenhum patrim?nio com respons?vel foi encontrado nesse projeto.', 422);
            }

            return $this->gerarZipPorColecao(
                patrimonios: $patrimonios,
                projeto: $projeto,
                nomeArquivoDownload: 'Termos de Responsabilidades ' . $cdProjeto . ' - ' . $this->formatarNomeProjetoParaTitulo((string) ($projeto->NOMEPROJETO ?? $cdProjeto)) . ' ' . now()->format('d-m-Y') . '.zip',
                contextoLog: [
                    'acao' => 'massa',
                    'cdprojeto' => $cdProjeto,
                ],
                origem: 'massa',
            );
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar termos de responsabilidade em lote', [
                'user_id' => Auth::id(),
                'erro' => $e->getMessage(),
                'arquivo' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return $this->responderErroLote($request, 'N?o foi poss?vel gerar e salvar os termos em lote.', 500);
        }
    }

    public function gerarDocxEmMassa(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', Patrimonio::class);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $validated = $request->validate([
            'cdprojeto' => ['required', 'string', 'max:20'],
        ], [
            'cdprojeto.required' => 'Informe o código do projeto para gerar os termos.',
            'cdprojeto.max' => 'O código do projeto informado é inválido.',
        ]);

        try {
            $cdProjeto = trim((string) $validated['cdprojeto']);
            $projeto = $this->buscarProjeto($cdProjeto);

            if (!$projeto) {
                return $this->responderErroLote($request, 'Projeto não encontrado para gerar os termos em lote.', 404);
            }

            $patrimonios = $this->buscarPatrimoniosPorProjeto($cdProjeto);

            if ($patrimonios->isEmpty()) {
                return $this->responderErroLote($request, 'Nenhum patrimônio com responsável foi encontrado nesse projeto.', 422);
            }

            return $this->gerarZipDocxPorColecao(
                patrimonios: $patrimonios,
                projeto: $projeto,
                nomeArquivoDownload: 'Termos de Responsabilidades ' . $cdProjeto . ' - ' . $this->formatarNomeProjetoParaTitulo((string) ($projeto->NOMEPROJETO ?? $cdProjeto)) . ' ' . now()->format('d-m-Y') . '.zip',
                contextoLog: [
                    'acao' => 'massa',
                    'cdprojeto' => $cdProjeto,
                ],
                origem: 'massa',
            );
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar termos de responsabilidade em lote (DOCX)', [
                'user_id' => Auth::id(),
                'erro' => $e->getMessage(),
                'arquivo' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return $this->responderErroLote($request, 'Não foi possível gerar e salvar os termos em lote.', 500);
        }
    }

    public function gerarPdfPorPatrimonio(Request $request, int $id): Response|RedirectResponse
    {
        $patrimonio = Patrimonio::query()
            ->with(['funcionario', 'local.projeto', 'projeto'])
            ->where('NUSEQPATR', $id)
            ->first();

        if (!$patrimonio) {
            return back()->with('error', 'Patrim?nio n?o encontrado para gerar o termo.');
        }

        app(PatrimonioLocalResolver::class)->attach($patrimonio);

        $this->authorize('view', $patrimonio);

        try {
            $forcarGeracao = $request->boolean('regenerar');

            if (!$forcarGeracao) {
                $arquivoSalvo = $this->buscarArquivoSalvoPorPatrimonio($id);
                if ($arquivoSalvo) {
                    return $this->baixarArquivoSalvo($arquivoSalvo);
                }
            }

            $cdProjeto = trim((string) ($patrimonio->CDPROJETO ?? ''));
            $matricula = trim((string) ($patrimonio->CDMATRFUNCIONARIO ?? ''));

            if ($cdProjeto === '' || $matricula === '') {
                return back()->with('warning', 'Esse patrim?nio precisa ter projeto e respons?vel para gerar o termo.');
            }

            $projeto = $this->buscarProjeto($cdProjeto);
            if (!$projeto) {
                return back()->with('error', 'Projeto n?o encontrado para gerar o termo deste patrim?nio.');
            }

            $patrimonios = $this->buscarPatrimoniosPorProjeto($cdProjeto)
                ->filter(fn (Patrimonio $item) => trim((string) $item->CDMATRFUNCIONARIO) === $matricula)
                ->values();

            if ($patrimonios->isEmpty()) {
                return back()->with('warning', 'Nenhum item eleg?vel foi encontrado para o respons?vel deste patrim?nio.');
            }

            $nomeResponsavel = $this->formatarNomeResponsavelParaTitulo((string) ($patrimonios->first()?->funcionario?->NMFUNCIONARIO ?? $matricula));

            return $this->gerarPdfPorColecao(
                patrimonios: $patrimonios,
                projeto: $projeto,
                nomeArquivoDownload: 'Termo de ' . $nomeResponsavel . '.pdf',
                contextoLog: [
                    'acao' => $forcarGeracao ? 'individual_regenerado' : 'individual',
                    'patrimonio_id' => $id,
                    'cdprojeto' => $cdProjeto,
                    'matricula' => $matricula,
                ],
                origem: 'individual',
            );
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar termo de responsabilidade por patrim?nio', [
                'user_id' => Auth::id(),
                'patrimonio_id' => $id,
                'erro' => $e->getMessage(),
                'arquivo' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return back()->with('error', 'N?o foi poss?vel gerar ou recuperar o termo desse patrim?nio.');
        }
    }

    private function buscarProjeto(string $cdProjeto): ?Tabfant
    {
        return Tabfant::query()->where('CDPROJETO', $cdProjeto)->first();
    }

    private function buscarPatrimoniosPorProjeto(string $cdProjeto): Collection
    {
        $patrimonios = Patrimonio::query()
            ->select([
                'NUSEQPATR',
                'NUPATRIMONIO',
                'DEPATRIMONIO',
                'CDMATRFUNCIONARIO',
                'CDPROJETO',
                'CDLOCAL',
                'CODOBJETO',
                'MARCA',
                'MODELO',
                'NUSERIE',
                'FLTERMORESPONSABILIDADE',
            ])
            ->with(['funcionario:CDMATRFUNCIONARIO,NMFUNCIONARIO'])
            ->where('CDPROJETO', $cdProjeto)
            ->whereNotNull('CDMATRFUNCIONARIO')
            ->where('CDMATRFUNCIONARIO', '<>', '')
            ->orderBy('CDMATRFUNCIONARIO')
            ->orderBy('NUPATRIMONIO')
            ->get();

        app(PatrimonioLocalResolver::class)->attachMany($patrimonios);

        return $patrimonios;
    }

    private function gerarPdfPorColecao(Collection $patrimonios, Tabfant $projeto, string $nomeArquivoDownload, array $contextoLog, string $origem): Response|RedirectResponse
    {
        $documentos = $this->montarDocumentos($patrimonios);

        if ($documentos->isEmpty()) {
            return back()->with('warning', 'Nenhum funcion?rio eleg?vel foi encontrado para gerar os termos.');
        }

        if ($documentos->count() > self::MAX_FUNCIONARIOS) {
            return back()->with('warning', 'Esse projeto possui muitos funcion?rios para gera??o imediata. Aplique um filtro menor e tente novamente.');
        }

        $dadosDocumento = $this->montarDadosDocumento($projeto);
        $totalItens = $documentos->sum(fn (array $documento) => count($documento['itens']));

        Log::info('Gerando termo de responsabilidade em PDF', array_merge($contextoLog, [
            'user_id' => Auth::id(),
            'funcionarios' => $documentos->count(),
            'itens' => $totalItens,
            'arquivo' => $nomeArquivoDownload,
        ]));

        $arquivosSalvos = $this->salvarArquivosPorFuncionarioEmLote($documentos, $dadosDocumento, $origem);
        $this->marcarPatrimoniosComTermo($patrimonios);

        $primeiroArquivo = $arquivosSalvos[0] ?? null;
        if (!$primeiroArquivo) {
            return back()->with('error', 'N?o foi poss?vel salvar o documento do termo.');
        }

        return response()->download(
            Storage::disk('local')->path($primeiroArquivo['caminho_arquivo']),
            $nomeArquivoDownload
        );
    }

    private function gerarZipPorColecao(Collection $patrimonios, Tabfant $projeto, string $nomeArquivoDownload, array $contextoLog, string $origem): Response|RedirectResponse
    {
        $documentos = $this->montarDocumentos($patrimonios);

        if ($documentos->isEmpty()) {
            return back()->with('warning', 'Nenhum funcion?rio eleg?vel foi encontrado para gerar os termos.');
        }

        if ($documentos->count() > self::MAX_FUNCIONARIOS) {
            return back()->with('warning', 'Esse projeto possui muitos funcion?rios para gera??o imediata. Aplique um filtro menor e tente novamente.');
        }

        $dadosDocumento = $this->montarDadosDocumento($projeto);
        $totalItens = $documentos->sum(fn (array $documento) => count($documento['itens']));

        Log::info('Gerando lote ZIP de termos de responsabilidade em PDF', array_merge($contextoLog, [
            'user_id' => Auth::id(),
            'funcionarios' => $documentos->count(),
            'itens' => $totalItens,
            'arquivo' => $nomeArquivoDownload,
        ]));

        $arquivosSalvos = $this->salvarArquivosPorFuncionarioEmLote($documentos, $dadosDocumento, $origem);
        $this->marcarPatrimoniosComTermo($patrimonios);

        if ($arquivosSalvos === []) {
            return back()->with('error', 'N?o foi poss?vel salvar os documentos do lote.');
        }

        $caminhoPacote = $this->criarZipTemporario($arquivosSalvos, $nomeArquivoDownload);
        $response = response()->download(
            Storage::disk('local')->path($caminhoPacote),
            $nomeArquivoDownload
        );
        $response->headers->set('X-Termos-Gerados', (string) $documentos->count());
        $response->headers->set('X-Itens-Gerados', (string) $totalItens);
        $response->headers->set('X-Projeto-Codigo', (string) ($projeto->CDPROJETO ?? ''));
        $response->headers->set('X-Projeto-Nome', (string) ($projeto->NOMEPROJETO ?? ''));
        $response->headers->set('X-Pacote-Formato', 'ZIP');

        return $response;
    }

    private function gerarZipDocxPorColecao(Collection $patrimonios, Tabfant $projeto, string $nomeArquivoDownload, array $contextoLog, string $origem): Response|RedirectResponse
    {
        $documentos = $this->montarDocumentos($patrimonios);

        if ($documentos->isEmpty()) {
            return back()->with('warning', 'Nenhum funcionário elegível foi encontrado para gerar os termos.');
        }

        if ($documentos->count() > self::MAX_FUNCIONARIOS) {
            return back()->with('warning', 'Esse projeto possui muitos funcionários para geração imediata. Aplique um filtro menor e tente novamente.');
        }

        $dadosDocumento = $this->montarDadosDocumento($projeto);
        $totalItens = $documentos->sum(fn (array $documento) => count($documento['itens']));

        Log::info('Gerando lote ZIP de termos de responsabilidade em DOCX', array_merge($contextoLog, [
            'user_id' => Auth::id(),
            'funcionarios' => $documentos->count(),
            'itens' => $totalItens,
            'arquivo' => $nomeArquivoDownload,
        ]));

        $arquivosSalvos = $this->salvarArquivosDocxPorFuncionarioEmLote($documentos, $dadosDocumento, $origem);
        $this->marcarPatrimoniosComTermo($patrimonios);

        if ($arquivosSalvos === []) {
            return back()->with('error', 'Não foi possível salvar os documentos do lote.');
        }

        $caminhoPacote = $this->criarZipTemporario($arquivosSalvos, $nomeArquivoDownload);
        $response = response()->download(
            Storage::disk('local')->path($caminhoPacote),
            $nomeArquivoDownload
        );
        $response->headers->set('X-Termos-Gerados', (string) $documentos->count());
        $response->headers->set('X-Itens-Gerados', (string) $totalItens);
        $response->headers->set('X-Projeto-Codigo', (string) ($projeto->CDPROJETO ?? ''));
        $response->headers->set('X-Projeto-Nome', (string) ($projeto->NOMEPROJETO ?? ''));
        $response->headers->set('X-Pacote-Formato', 'ZIP');

        return $response;
    }

    private function salvarArquivosDocxPorFuncionarioEmLote(Collection $documentos, array $dadosDocumento, string $origem): array
    {
        $loginGerador = trim((string) (Auth::user()?->NMLOGIN ?? 'sistema'));
        $nomesVisiveisUsados = [];
        $arquivosTemporarios = [];
        $arquivosSalvos = [];

        try {
            foreach ($documentos as $documento) {
                $matricula = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($documento['matricula'] ?? 'sem_matricula'));
                $nomeInterno = $this->normalizarTrechoNomeArquivo((string) ($documento['nome'] ?? $matricula));
                $nomeVisivel = $this->formatarNomeResponsavelParaTitulo((string) ($documento['nome'] ?? $matricula));
                $cdProjeto = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($dadosDocumento['projeto']->CDPROJETO ?? 'sem_projeto'));
                $timestamp = now()->format('d-m-Y_His_u');

                $nomeArquivo = $this->garantirNomeArquivoUnico('Termo de ' . $nomeVisivel . '.docx', $nomesVisiveisUsados, (string) ($documento['matricula'] ?? ''));
                $nomeArquivoInterno = 'termo_responsabilidade_' . $nomeInterno . '_' . $matricula . '_' . $timestamp . '.docx';
                $caminhoArquivo = 'termos_responsabilidade/' . $cdProjeto . '/' . $matricula . '/' . $nomeArquivoInterno;

                $arquivoDocx = $this->criarArquivoTemporarioDocx($documento, $dadosDocumento);

                $arquivosTemporarios[] = [
                    'arquivo_docx' => $arquivoDocx,
                    'nome_arquivo' => $nomeArquivo,
                    'caminho_arquivo' => $caminhoArquivo,
                    'documento' => $documento,
                ];
            }

            foreach ($arquivosTemporarios as $arquivoTemporario) {
                $caminhoArquivo = $arquivoTemporario['caminho_arquivo'];
                $arquivoDocx = $arquivoTemporario['arquivo_docx'];
                $documento = $arquivoTemporario['documento'];
                $nomeArquivo = $arquivoTemporario['nome_arquivo'];

                Storage::disk('local')->put($caminhoArquivo, file_get_contents($arquivoDocx));

                DB::transaction(function () use ($documento, $dadosDocumento, $nomeArquivo, $caminhoArquivo, $origem, $loginGerador) {
                    $arquivo = TermoResponsabilidadeArquivo::create([
                        'cdprojeto' => (string) ($dadosDocumento['projeto']->CDPROJETO ?? ''),
                        'cdmatrfuncionario' => (string) ($documento['matricula'] ?? ''),
                        'nome_arquivo' => $nomeArquivo,
                        'caminho_arquivo' => $caminhoArquivo,
                        'total_itens' => count($documento['itens'] ?? []),
                        'origem' => $origem,
                        'gerado_por' => $loginGerador,
                        'gerado_em' => now(),
                    ]);

                    $itens = collect($documento['itens'] ?? [])
                        ->pluck('patrimonio_id')
                        ->filter()
                        ->map(fn ($id) => [
                            'termo_responsabilidade_arquivo_id' => $arquivo->id,
                            'nuseqpatr' => (int) $id,
                        ])
                        ->values()
                        ->all();

                    if ($itens !== []) {
                        TermoResponsabilidadeArquivoItem::insert($itens);
                    }
                });

                $arquivosSalvos[] = [
                    'nome_arquivo' => $nomeArquivo,
                    'caminho_arquivo' => $caminhoArquivo,
                ];
            }
        } finally {
            foreach ($arquivosTemporarios as $arquivoTemporario) {
                if (isset($arquivoTemporario['arquivo_docx']) && is_file($arquivoTemporario['arquivo_docx'])) {
                    @unlink($arquivoTemporario['arquivo_docx']);
                }
            }
        }

        return $arquivosSalvos;
    }

    private function montarDadosDocumento(Tabfant $projeto): array
    {
        $this->validarTemplateReferencia();

        $agora = now()->locale('pt_BR');
        $localAssinatura = $this->montarLocalAssinatura($projeto);

        return [
            'projeto' => $projeto,
            'templateReferencia' => base_path(self::TEMPLATE_REFERENCIA_PATH),
            'localAssinatura' => $localAssinatura,
            'dataCurta' => $agora->format('d/m/Y'),
            'dataExtenso' => $localAssinatura . ', ' . $agora->isoFormat('DD [de] MMMM [de] YYYY'),
        ];
    }

    private function validarTemplateReferencia(): void
    {
        if (is_file(base_path(self::TEMPLATE_REFERENCIA_PATH))) {
            return;
        }

        throw new \RuntimeException('Modelo do termo de responsabilidade n?o encontrado em storage/app/templates.');
    }

    private function salvarArquivosPorFuncionario(Collection $documentos, array $dadosDocumento, string $origem): array
    {
        $loginGerador = trim((string) (Auth::user()?->NMLOGIN ?? 'sistema'));
        $arquivosSalvos = [];
        $nomesVisiveisUsados = [];

        foreach ($documentos as $documento) {
            $matricula = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($documento['matricula'] ?? 'sem_matricula'));
            $nomeInterno = $this->normalizarTrechoNomeArquivo((string) ($documento['nome'] ?? $matricula));
            $nomeVisivel = $this->formatarNomeResponsavelParaTitulo((string) ($documento['nome'] ?? $matricula));
            $cdProjeto = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($dadosDocumento['projeto']->CDPROJETO ?? 'sem_projeto'));
            $timestamp = now()->format('d-m-Y_His_u');

            $nomeArquivo = $this->garantirNomeArquivoUnico('Termo de ' . $nomeVisivel . '.pdf', $nomesVisiveisUsados, (string) ($documento['matricula'] ?? ''));
            $nomeArquivoInterno = 'termo_responsabilidade_' . $nomeInterno . '_' . $matricula . '_' . $timestamp . '.pdf';
            $caminhoArquivo = 'termos_responsabilidade/' . $cdProjeto . '/' . $matricula . '/' . $nomeArquivoInterno;

            $arquivoDocx = $this->criarArquivoTemporarioDocx($documento, $dadosDocumento);
            $arquivoPdf = $this->converterDocxParaPdfTemporario($arquivoDocx);

            try {
                Storage::disk('local')->put($caminhoArquivo, file_get_contents($arquivoPdf));

                DB::transaction(function () use ($documento, $dadosDocumento, $nomeArquivo, $caminhoArquivo, $origem, $loginGerador) {
                    $arquivo = TermoResponsabilidadeArquivo::create([
                        'cdprojeto' => (string) ($dadosDocumento['projeto']->CDPROJETO ?? ''),
                        'cdmatrfuncionario' => (string) ($documento['matricula'] ?? ''),
                        'nome_arquivo' => $nomeArquivo,
                        'caminho_arquivo' => $caminhoArquivo,
                        'total_itens' => count($documento['itens'] ?? []),
                        'origem' => $origem,
                        'gerado_por' => $loginGerador,
                        'gerado_em' => now(),
                    ]);

                    $itens = collect($documento['itens'] ?? [])
                        ->pluck('patrimonio_id')
                        ->filter()
                        ->map(fn ($id) => [
                            'termo_responsabilidade_arquivo_id' => $arquivo->id,
                            'nuseqpatr' => (int) $id,
                        ])
                        ->values()
                        ->all();

                    if ($itens !== []) {
                        TermoResponsabilidadeArquivoItem::insert($itens);
                    }
                });

                $arquivosSalvos[] = [
                    'nome_arquivo' => $nomeArquivo,
                    'caminho_arquivo' => $caminhoArquivo,
                ];
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($caminhoArquivo);
                throw $e;
            } finally {
                if (is_file($arquivoPdf)) {
                    @unlink($arquivoPdf);
                }
                if (is_file($arquivoDocx)) {
                    @unlink($arquivoDocx);
                }
            }
        }

        return $arquivosSalvos;
    }

    private function salvarArquivosPorFuncionarioEmLote(Collection $documentos, array $dadosDocumento, string $origem): array
    {
        if (!$this->podeUsarConversaoPdfWord()) {
            return $this->salvarArquivosPdfViaHtmlPorFuncionarioEmLote($documentos, $dadosDocumento, $origem);
        }

        $loginGerador = trim((string) (Auth::user()?->NMLOGIN ?? 'sistema'));
        $nomesVisiveisUsados = [];
        $arquivosTemporarios = [];
        $arquivosSalvos = [];

        try {
            foreach ($documentos as $documento) {
                $matricula = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($documento['matricula'] ?? 'sem_matricula'));
                $nomeInterno = $this->normalizarTrechoNomeArquivo((string) ($documento['nome'] ?? $matricula));
                $nomeVisivel = $this->formatarNomeResponsavelParaTitulo((string) ($documento['nome'] ?? $matricula));
                $cdProjeto = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($dadosDocumento['projeto']->CDPROJETO ?? 'sem_projeto'));
                $timestamp = now()->format('d-m-Y_His_u');

                $nomeArquivo = $this->garantirNomeArquivoUnico('Termo de ' . $nomeVisivel . '.pdf', $nomesVisiveisUsados, (string) ($documento['matricula'] ?? ''));
                $nomeArquivoInterno = 'termo_responsabilidade_' . $nomeInterno . '_' . $matricula . '_' . $timestamp . '.pdf';
                $caminhoArquivo = 'termos_responsabilidade/' . $cdProjeto . '/' . $matricula . '/' . $nomeArquivoInterno;
                $arquivoDocx = $this->criarArquivoTemporarioDocx($documento, $dadosDocumento);
                $arquivoPdf = preg_replace('/\.docx$/i', '.pdf', $arquivoDocx);

                if (!is_string($arquivoPdf) || $arquivoPdf === '') {
                    throw new \RuntimeException('Não foi possível preparar os arquivos temporários do lote.');
                }

                $arquivosTemporarios[] = [
                    'documento' => $documento,
                    'nome_arquivo' => $nomeArquivo,
                    'caminho_arquivo' => $caminhoArquivo,
                    'arquivo_docx' => $arquivoDocx,
                    'arquivo_pdf' => $arquivoPdf,
                ];
            }

            $this->converterDocxParaPdfEmLote($arquivosTemporarios);

            foreach ($arquivosTemporarios as $arquivoTemporario) {
                $caminhoArquivo = $arquivoTemporario['caminho_arquivo'];
                $arquivoPdf = $arquivoTemporario['arquivo_pdf'];
                $documento = $arquivoTemporario['documento'];
                $nomeArquivo = $arquivoTemporario['nome_arquivo'];

                Storage::disk('local')->put($caminhoArquivo, file_get_contents($arquivoPdf));

                DB::transaction(function () use ($documento, $dadosDocumento, $nomeArquivo, $caminhoArquivo, $origem, $loginGerador) {
                    $arquivo = TermoResponsabilidadeArquivo::create([
                        'cdprojeto' => (string) ($dadosDocumento['projeto']->CDPROJETO ?? ''),
                        'cdmatrfuncionario' => (string) ($documento['matricula'] ?? ''),
                        'nome_arquivo' => $nomeArquivo,
                        'caminho_arquivo' => $caminhoArquivo,
                        'total_itens' => count($documento['itens'] ?? []),
                        'origem' => $origem,
                        'gerado_por' => $loginGerador,
                        'gerado_em' => now(),
                    ]);

                    $itens = collect($documento['itens'] ?? [])
                        ->pluck('patrimonio_id')
                        ->filter()
                        ->map(fn ($id) => [
                            'termo_responsabilidade_arquivo_id' => $arquivo->id,
                            'nuseqpatr' => (int) $id,
                        ])
                        ->values()
                        ->all();

                    if ($itens !== []) {
                        TermoResponsabilidadeArquivoItem::insert($itens);
                    }
                });

                $arquivosSalvos[] = [
                    'nome_arquivo' => $nomeArquivo,
                    'caminho_arquivo' => $caminhoArquivo,
                ];
            }
        } finally {
            foreach ($arquivosTemporarios as $arquivoTemporario) {
                if (isset($arquivoTemporario['arquivo_pdf']) && is_file($arquivoTemporario['arquivo_pdf'])) {
                    @unlink($arquivoTemporario['arquivo_pdf']);
                }
                if (isset($arquivoTemporario['arquivo_docx']) && is_file($arquivoTemporario['arquivo_docx'])) {
                    @unlink($arquivoTemporario['arquivo_docx']);
                }
            }
        }

        return $arquivosSalvos;
    }

    private function salvarArquivosPdfViaHtmlPorFuncionarioEmLote(Collection $documentos, array $dadosDocumento, string $origem): array
    {
        $loginGerador = trim((string) (Auth::user()?->NMLOGIN ?? 'sistema'));
        $nomesVisiveisUsados = [];
        $arquivosSalvos = [];

        foreach ($documentos as $documento) {
            $matricula = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($documento['matricula'] ?? 'sem_matricula'));
            $nomeInterno = $this->normalizarTrechoNomeArquivo((string) ($documento['nome'] ?? $matricula));
            $nomeVisivel = $this->formatarNomeResponsavelParaTitulo((string) ($documento['nome'] ?? $matricula));
            $cdProjeto = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($dadosDocumento['projeto']->CDPROJETO ?? 'sem_projeto'));
            $timestamp = now()->format('d-m-Y_His_u');

            $nomeArquivo = $this->garantirNomeArquivoUnico('Termo de ' . $nomeVisivel . '.pdf', $nomesVisiveisUsados, (string) ($documento['matricula'] ?? ''));
            $nomeArquivoInterno = 'termo_responsabilidade_' . $nomeInterno . '_' . $matricula . '_' . $timestamp . '.pdf';
            $caminhoArquivo = 'termos_responsabilidade/' . $cdProjeto . '/' . $matricula . '/' . $nomeArquivoInterno;

            Storage::disk('local')->put($caminhoArquivo, $this->gerarPdfBinarioViaHtml($documento, $dadosDocumento));

            DB::transaction(function () use ($documento, $dadosDocumento, $nomeArquivo, $caminhoArquivo, $origem, $loginGerador) {
                $arquivo = TermoResponsabilidadeArquivo::create([
                    'cdprojeto' => (string) ($dadosDocumento['projeto']->CDPROJETO ?? ''),
                    'cdmatrfuncionario' => (string) ($documento['matricula'] ?? ''),
                    'nome_arquivo' => $nomeArquivo,
                    'caminho_arquivo' => $caminhoArquivo,
                    'total_itens' => count($documento['itens'] ?? []),
                    'origem' => $origem,
                    'gerado_por' => $loginGerador,
                    'gerado_em' => now(),
                ]);

                $itens = collect($documento['itens'] ?? [])
                    ->pluck('patrimonio_id')
                    ->filter()
                    ->map(fn ($id) => [
                        'termo_responsabilidade_arquivo_id' => $arquivo->id,
                        'nuseqpatr' => (int) $id,
                    ])
                    ->values()
                    ->all();

                if ($itens !== []) {
                    TermoResponsabilidadeArquivoItem::insert($itens);
                }
            });

            $arquivosSalvos[] = [
                'nome_arquivo' => $nomeArquivo,
                'caminho_arquivo' => $caminhoArquivo,
            ];
        }

        return $arquivosSalvos;
    }

    private function converterDocxParaPdfEmLote(array $arquivosTemporarios): void
    {
        if ($arquivosTemporarios === []) {
            return;
        }

        $caminhoScript = $this->criarScriptTemporarioConversaoPdfLote();
        $caminhoManifesto = tempnam(sys_get_temp_dir(), 'word_pdf_manifest_');

        if ($caminhoManifesto === false) {
            throw new \RuntimeException('Não foi possível preparar o manifesto temporário do lote.');
        }

        try {
            $linhasManifesto = array_map(function (array $arquivoTemporario) {
                return $arquivoTemporario['arquivo_docx'] . "\t" . $arquivoTemporario['arquivo_pdf'];
            }, $arquivosTemporarios);

            file_put_contents($caminhoManifesto, implode(PHP_EOL, $linhasManifesto));

            $process = new Process([
                $this->resolverExecutavelCscript(),
                '//NoLogo',
                $caminhoScript,
                $caminhoManifesto,
            ]);
            $process->setTimeout(1800);
            $process->run();

            if (!$process->isSuccessful()) {
                $detalhe = trim($process->getErrorOutput() . PHP_EOL . $process->getOutput());
                throw new \RuntimeException('Não foi possível converter o lote de termos para PDF.' . ($detalhe !== '' ? ' Detalhe: ' . $detalhe : ''));
            }

            $arquivosPendentes = [];

            foreach ($arquivosTemporarios as $arquivoTemporario) {
                $arquivoPdf = $arquivoTemporario['arquivo_pdf'];
                if (!is_file($arquivoPdf) || filesize($arquivoPdf) <= 0) {
                    $arquivosPendentes[] = $arquivoTemporario;
                }
            }

            foreach ($arquivosPendentes as $arquivoTemporario) {
                $this->converterDocxParaPdfTemporario($arquivoTemporario['arquivo_docx']);
            }

            $faltantes = [];
            foreach ($arquivosTemporarios as $arquivoTemporario) {
                $arquivoPdf = $arquivoTemporario['arquivo_pdf'];
                if (!is_file($arquivoPdf) || filesize($arquivoPdf) <= 0) {
                    $faltantes[] = basename((string) $arquivoTemporario['arquivo_docx']);
                }
            }

            if ($faltantes !== []) {
                throw new \RuntimeException('Nem todos os PDFs do lote foram gerados corretamente. Arquivos pendentes: ' . implode(', ', array_slice($faltantes, 0, 5)));
            }
        } finally {
            if (is_file($caminhoScript)) {
                @unlink($caminhoScript);
            }
            if (is_file($caminhoManifesto)) {
                @unlink($caminhoManifesto);
            }
        }
    }

    private function criarArquivoTemporarioDocx(array $documento, array $dadosDocumento): string
    {
        $tempBase = tempnam(sys_get_temp_dir(), 'termo_resp_');
        if ($tempBase === false) {
            throw new \RuntimeException('N?o foi poss?vel preparar o arquivo tempor?rio do termo.');
        }

        $arquivoTemporario = $tempBase . '.docx';
        @unlink($tempBase);

        $template = new TemplateProcessor($dadosDocumento['templateReferencia']);
        $template->setValue('employee_name', $this->sanitizeText((string) ($documento['nome'] ?? 'NÃO INFORMADO')));
        $template->setValue('employee_matricula', $this->sanitizeText((string) ($documento['matricula'] ?? 'S/N')));
        $template->setValue('date_extenso', $this->sanitizeText((string) ($dadosDocumento['dataExtenso'] ?? '')));
        $template->setValue('date_short', $this->sanitizeText((string) ($dadosDocumento['dataCurta'] ?? '')));
        $template->setValue('cidade', $this->sanitizeText((string) ($dadosDocumento['localAssinatura'] ?? '')));

        $itens = collect($documento['itens'] ?? [])->values();

        if ($itens->isEmpty()) {
            $template->setValue('item_name', 'Nenhum item vinculado');
            $template->setValue('item_qty', '0');
        } else {
            $template->cloneRow('item_name', $itens->count());

            foreach ($itens as $index => $item) {
                $linha = $index + 1;
                $template->setValue('item_name#' . $linha, $this->sanitizeText((string) ($item['descricao'] ?? 'Item sem descricao')));
                $template->setValue('item_qty#' . $linha, (string) ($item['quantidade'] ?? 1));
            }
        }

        $template->saveAs($arquivoTemporario);
        $this->normalizarEstiloDocumento($arquivoTemporario);

        return $arquivoTemporario;
    }

    private function converterDocxParaPdfTemporario(string $caminhoDocx): string
    {
        $caminhoPdf = preg_replace('/\.docx$/i', '.pdf', $caminhoDocx);
        if (!is_string($caminhoPdf) || $caminhoPdf === '') {
            throw new \RuntimeException('Não foi possível preparar a conversão do termo para PDF.');
        }

        $caminhoScript = $this->criarScriptTemporarioConversaoPdf();

        try {
            $ultimaSaidaErro = '';

            for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
                if (is_file($caminhoPdf)) {
                    @unlink($caminhoPdf);
                }

                clearstatcache(true, $caminhoDocx);
                usleep(250000);

                $process = new Process([
                    $this->resolverExecutavelCscript(),
                    '//NoLogo',
                    $caminhoScript,
                    $caminhoDocx,
                    $caminhoPdf,
                ]);
                $process->setTimeout(180);
                $process->run();

                if ($process->isSuccessful() && is_file($caminhoPdf) && filesize($caminhoPdf) > 0) {
                    return $caminhoPdf;
                }

                $ultimaSaidaErro = trim($process->getErrorOutput() . PHP_EOL . $process->getOutput());
                usleep(500000);
            }

            throw new \RuntimeException('Não foi possível converter o termo para PDF.' . ($ultimaSaidaErro !== '' ? ' Detalhe: ' . $ultimaSaidaErro : ''));
        } finally {
            if (is_file($caminhoScript)) {
                @unlink($caminhoScript);
            }
        }
    }

    private function criarScriptTemporarioConversaoPdf(): string
    {
        $caminhoTemporario = tempnam(sys_get_temp_dir(), 'word_pdf_');
        if ($caminhoTemporario === false) {
            throw new \RuntimeException('N?o foi poss?vel preparar o script tempor?rio de convers?o.');
        }

        $caminhoScript = $caminhoTemporario . '.vbs';
        @unlink($caminhoTemporario);

        $script = <<<'VBS'
Dim sourcePath
Dim targetPath
sourcePath = WScript.Arguments.Item(0)
targetPath = WScript.Arguments.Item(1)

Dim fso
Set fso = CreateObject("Scripting.FileSystemObject")

Dim wordApp
Set wordApp = CreateObject("Word.Application")
wordApp.Visible = False
wordApp.DisplayAlerts = 0

On Error Resume Next

If fso.FileExists(targetPath) Then
    fso.DeleteFile targetPath, True
End If

Dim doc
Set doc = wordApp.Documents.Open(sourcePath, False, True)

If Err.Number <> 0 Then
    WScript.Echo "Falha ao abrir DOCX: " & sourcePath & " | " & Err.Description
    wordApp.Quit
    WScript.Quit 3
End If

Err.Clear
doc.ExportAsFixedFormat targetPath, 17

If Err.Number <> 0 Then
    doc.Close False
    WScript.Echo "Falha ao exportar PDF: " & targetPath & " | " & Err.Description
    wordApp.Quit
    WScript.Quit 4
End If

doc.Close False
wordApp.Quit
WScript.Quit 0
VBS;

        file_put_contents($caminhoScript, $script);

        return $caminhoScript;
    }

    private function podeUsarConversaoPdfWord(): bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        try {
            $this->resolverExecutavelCscript();

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    private function resolverExecutavelCscript(): string
    {
        $windir = getenv('WINDIR');
        if (is_string($windir) && $windir !== '') {
            $caminho = $windir . DIRECTORY_SEPARATOR . 'System32' . DIRECTORY_SEPARATOR . 'cscript.exe';
            if (is_file($caminho)) {
                return $caminho;
            }
        }

        $output = @shell_exec('where cscript 2>NUL');
        $linhas = preg_split('/\r\n|\r|\n/', trim((string) $output)) ?: [];

        foreach ($linhas as $linha) {
            $caminho = trim($linha);
            if ($caminho !== '' && is_file($caminho)) {
                return $caminho;
            }
        }

        throw new \RuntimeException('O executável cscript.exe não está disponível neste ambiente.');
    }

    private function gerarPdfBinarioViaHtml(array $documento, array $dadosDocumento): string
    {
        $pdf = Pdf::loadView('termos.responsabilidade-pdf', [
            'documento' => $documento,
            'dadosDocumento' => $dadosDocumento,
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    private function criarScriptTemporarioConversaoPdfLote(): string
    {
        $caminhoTemporario = tempnam(sys_get_temp_dir(), 'word_pdf_batch_');
        if ($caminhoTemporario === false) {
            throw new \RuntimeException('Não foi possível preparar o script temporário do lote.');
        }

        $caminhoScript = $caminhoTemporario . '.vbs';
        @unlink($caminhoTemporario);

        $script = <<<'VBS'
Dim manifestPath
manifestPath = WScript.Arguments.Item(0)

Dim fso
Set fso = CreateObject("Scripting.FileSystemObject")

If Not fso.FileExists(manifestPath) Then
    WScript.Echo "Manifesto do lote nao encontrado."
    WScript.Quit 2
End If

Dim wordApp
Set wordApp = CreateObject("Word.Application")
wordApp.Visible = False
wordApp.DisplayAlerts = 0

On Error Resume Next

Dim manifestFile
Set manifestFile = fso.OpenTextFile(manifestPath, 1, False)

Do Until manifestFile.AtEndOfStream
    Dim line
    line = Trim(manifestFile.ReadLine)

    If line <> "" Then
        Dim parts
        parts = Split(line, vbTab)

        If UBound(parts) >= 1 Then
            Dim sourcePath
            Dim targetPath
            sourcePath = parts(0)
            targetPath = parts(1)

            If fso.FileExists(targetPath) Then
                fso.DeleteFile targetPath, True
            End If

            Dim doc
            Set doc = wordApp.Documents.Open(sourcePath, False, True)

            If Err.Number <> 0 Then
                WScript.Echo "Falha ao abrir DOCX: " & sourcePath & " | " & Err.Description
                wordApp.Quit
                WScript.Quit 3
            End If

            Err.Clear
            doc.ExportAsFixedFormat targetPath, 17

            If Err.Number <> 0 Then
                doc.Close False
                WScript.Echo "Falha ao exportar PDF: " & targetPath & " | " & Err.Description
                wordApp.Quit
                WScript.Quit 4
            End If

            doc.Close False
            Set doc = Nothing
        End If
    End If
Loop

manifestFile.Close
wordApp.Quit
WScript.Quit 0
VBS;

        file_put_contents($caminhoScript, $script);

        return $caminhoScript;
    }

    private function normalizarEstiloDocumento(string $caminhoArquivo): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($caminhoArquivo) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (!is_string($xml) || $xml === '') {
            $zip->close();
            return;
        }

        $xml = preg_replace('/<w:color\\b[^>]*w:val=\"FF0000\"[^>]*\\/>/i', '<w:color w:val="000000"/>', $xml) ?? $xml;
        $xml = preg_replace('/<w:color\\b[^>]*w:val=\"ff0000\"[^>]*\\/>/i', '<w:color w:val="000000"/>', $xml) ?? $xml;
        $xml = $this->aplicarMargemEstreitaAoDocumento($xml);

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    private function aplicarMargemEstreitaAoDocumento(string $xml): string
    {
        return preg_replace_callback('/<w:pgMar\b[^>]*\/>/i', function (array $matches) {
            $tag = $matches[0];
            $margens = [
                'top' => '720',
                'right' => '720',
                'bottom' => '720',
                'left' => '720',
                'header' => '360',
                'footer' => '360',
                'gutter' => '0',
            ];

            foreach ($margens as $atributo => $valor) {
                if (preg_match('/w:' . $atributo . '="[^"]*"/i', $tag)) {
                    $tag = preg_replace('/w:' . $atributo . '="[^"]*"/i', 'w:' . $atributo . '="' . $valor . '"', $tag) ?? $tag;
                } else {
                    $tag = rtrim(substr($tag, 0, -2)) . ' w:' . $atributo . '="' . $valor . '"/>';
                }
            }

            return $tag;
        }, $xml) ?? $xml;
    }

    private function buscarArquivoSalvoPorPatrimonio(int $patrimonioId): ?TermoResponsabilidadeArquivo
    {
        $arquivo = TermoResponsabilidadeArquivo::query()
            ->whereHas('itens', function ($query) use ($patrimonioId) {
                $query->where('nuseqpatr', $patrimonioId);
            })
            ->orderByDesc('gerado_em')
            ->orderByDesc('id')
            ->first();

        if (!$arquivo) {
            return null;
        }

        if (!Storage::disk('local')->exists($arquivo->caminho_arquivo)) {
            Log::warning('Arquivo de termo salvo n?o encontrado em disco', [
                'arquivo_id' => $arquivo->id,
                'caminho' => $arquivo->caminho_arquivo,
            ]);

            return null;
        }

        return $arquivo;
    }

    private function baixarArquivoSalvo(TermoResponsabilidadeArquivo $arquivo): Response
    {
        Log::info('Baixando termo de responsabilidade salvo', [
            'arquivo_id' => $arquivo->id,
            'user_id' => Auth::id(),
            'caminho' => $arquivo->caminho_arquivo,
        ]);

        return response()->download(
            Storage::disk('local')->path($arquivo->caminho_arquivo),
            $arquivo->nome_arquivo
        );
    }

    private function criarZipTemporario(array $arquivosSalvos, string $nomeArquivoDownload): string
    {
        $this->limparPacotesTemporariosAntigos();

        $nomePacote = pathinfo($nomeArquivoDownload, PATHINFO_FILENAME) . '.zip';
        $caminhoPacote = 'termos_responsabilidade/tmp/' . $nomePacote;
        $caminhoReal = Storage::disk('local')->path($caminhoPacote);

        $diretorio = dirname($caminhoReal);
        if (!is_dir($diretorio) && !mkdir($diretorio, 0777, true) && !is_dir($diretorio)) {
            throw new \RuntimeException('Não foi possível preparar a pasta temporária do lote.');
        }

        $zip = new \ZipArchive();
        $resultado = $zip->open($caminhoReal, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($resultado !== true) {
            throw new \RuntimeException('Não foi possível criar o arquivo ZIP do lote.');
        }

        $arquivosAdicionados = 0;

        foreach ($arquivosSalvos as $arquivo) {
            $caminhoAbsoluto = Storage::disk('local')->path($arquivo['caminho_arquivo']);
            if (!is_file($caminhoAbsoluto)) {
                continue;
            }

            $nomeInterno = trim((string) ($arquivo['nome_arquivo'] ?? basename($caminhoAbsoluto)));
            if ($zip->addFile($caminhoAbsoluto, $nomeInterno)) {
                $arquivosAdicionados++;
            }
        }

        $zip->close();

        if ($arquivosAdicionados === 0 || !is_file($caminhoReal) || filesize($caminhoReal) <= 0) {
            throw new \RuntimeException('Nenhum arquivo foi encontrado para montar o pacote do lote.');
        }

        return $caminhoPacote;
    }

    private function criarRarTemporario(array $arquivosSalvos, string $nomeArquivoDownload): string
    {
        $this->limparPacotesTemporariosAntigos();

        $nomePacote = pathinfo($nomeArquivoDownload, PATHINFO_FILENAME) . '.rar';
        $caminhoPacote = 'termos_responsabilidade/tmp/' . $nomePacote;
        $caminhoReal = Storage::disk('local')->path($caminhoPacote);

        $diretorio = dirname($caminhoReal);
        if (!is_dir($diretorio) && !mkdir($diretorio, 0777, true) && !is_dir($diretorio)) {
            throw new \RuntimeException('N?o foi poss?vel preparar a pasta tempor?ria do lote.');
        }

        $arquivos = [];
        foreach ($arquivosSalvos as $arquivo) {
            $caminhoAbsoluto = Storage::disk('local')->path($arquivo['caminho_arquivo']);
            if (is_file($caminhoAbsoluto)) {
                $arquivos[] = $caminhoAbsoluto;
            }
        }

        if ($arquivos === []) {
            throw new \RuntimeException('Nenhum arquivo foi encontrado para montar o pacote do lote.');
        }

        $rarExe = $this->resolverExecutavelRar();
        $process = new Process(array_merge([$rarExe, 'a', '-ep1', '-idq', $caminhoReal], $arquivos));
        $process->setTimeout(180);
        $process->run();

        if (!$process->isSuccessful() || !is_file($caminhoReal)) {
            throw new \RuntimeException('N?o foi poss?vel gerar o arquivo RAR do lote.');
        }

        return $caminhoPacote;
    }

    private function limparPacotesTemporariosAntigos(): void
    {
        $disk = Storage::disk('local');
        $diretorio = 'termos_responsabilidade/tmp';

        if (!$disk->exists($diretorio)) {
            return;
        }

        $limite = now()->subHours(6)->getTimestamp();

        foreach ($disk->files($diretorio) as $arquivo) {
            $caminhoAbsoluto = $disk->path($arquivo);
            $modificadoEm = @filemtime($caminhoAbsoluto);

            if ($modificadoEm !== false && $modificadoEm < $limite) {
                $disk->delete($arquivo);
            }
        }
    }

    private function montarDocumentos(Collection $patrimonios): Collection
    {
        $descMap = $this->carregarDescricoesObjetos($patrimonios);

        return $patrimonios
            ->groupBy(fn (Patrimonio $patrimonio) => trim((string) $patrimonio->CDMATRFUNCIONARIO))
            ->map(function (Collection $itens, string $matricula) use ($descMap) {
                $funcionario = $itens->first()?->funcionario;
                $nomeFuncionario = $this->resolverNomeFuncionario($funcionario, $matricula);

                $linhas = $itens
                    ->map(function (Patrimonio $patrimonio) use ($descMap) {
                        return [
                            'patrimonio_id' => (int) ($patrimonio->NUSEQPATR ?? 0),
                            'descricao' => $this->montarDescricaoItem($patrimonio, $descMap),
                            'quantidade' => 1,
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'matricula' => $matricula,
                    'nome' => $nomeFuncionario,
                    'itens' => $linhas,
                ];
            })
            ->filter(fn (array $documento) => !empty($documento['itens']))
            ->sortBy(fn (array $documento) => mb_strtolower($documento['nome']) . '|' . $documento['matricula'])
            ->values();
    }

    private function carregarDescricoesObjetos(Collection $patrimonios): array
    {
        $codigos = $patrimonios
            ->pluck('CODOBJETO')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($codigos === []) {
            return [];
        }

        $objetoPatr = new ObjetoPatr();
        $pkColumn = $objetoPatr->getKeyName();

        return ObjetoPatr::query()
            ->whereIn($pkColumn, $codigos)
            ->pluck('DEOBJETO', $pkColumn)
            ->toArray();
    }

    private function resolverNomeFuncionario(?Funcionario $funcionario, string $matricula): string
    {
        $nome = trim((string) ($funcionario?->NMFUNCIONARIO ?? ''));

        if ($nome !== '') {
            return $nome;
        }

        return 'Funcionario ' . $matricula;
    }

    private function montarDescricaoItem(Patrimonio $patrimonio, array $descMap): string
    {
        $descricao = trim((string) ($patrimonio->DEPATRIMONIO ?? ''));

        if ($descricao === '') {
            $descricaoObjeto = trim((string) ($descMap[$patrimonio->CODOBJETO] ?? ''));
            if ($descricaoObjeto !== '') {
                $descricao = $descricaoObjeto;
            }
        }

        if ($descricao === '') {
            $descricao = trim((string) ($patrimonio->MARCA ?? ''));
        }

        if ($descricao === '') {
            $descricao = 'Item sem descricao';
        }

        $descricaoCompleta = $descricao;
        $numeroPatrimonio = trim((string) ($patrimonio->NUPATRIMONIO ?? ''));

        if ($numeroPatrimonio !== '') {
            $descricaoCompleta = 'PAT ' . $numeroPatrimonio . ' - ' . $descricaoCompleta;
        }

        $modelo = trim((string) ($patrimonio->MODELO ?? ''));
        if ($modelo !== '') {
            $descricaoCompleta .= ' - ' . $modelo;
        }

        $serie = trim((string) ($patrimonio->NUSERIE ?? ''));
        if ($serie !== '') {
            $descricaoCompleta .= ' (S/N: ' . $serie . ')';
        }

        return $descricaoCompleta;
    }

    private function montarLocalAssinatura(Tabfant $projeto): string
    {
        $local = trim((string) ($projeto->LOCAL ?? ''));
        $uf = strtoupper(trim((string) ($projeto->UF ?? '')));

        if ($local !== '' && $uf !== '') {
            return $local . '/' . $uf;
        }

        if ($local !== '') {
            return $local;
        }

        if ($uf !== '') {
            return $this->resolverNomeUf($uf);
        }

        return 'Local nao informado';
    }

    private function resolverNomeUf(string $sigla): string
    {
        $estados = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapa',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceara',
            'DF' => 'Distrito Federal',
            'ES' => 'Espirito Santo',
            'GO' => 'Goias',
            'MA' => 'Maranhao',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Para',
            'PB' => 'Paraiba',
            'PR' => 'Parana',
            'PE' => 'Pernambuco',
            'PI' => 'Piaui',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondonia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'Sao Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        return $estados[$sigla] ?? $sigla;
    }

    private function marcarPatrimoniosComTermo(Collection $patrimonios): void
    {
        $ids = $patrimonios
            ->pluck('NUSEQPATR')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        Patrimonio::query()
            ->whereIn('NUSEQPATR', $ids->all())
            ->update(['FLTERMORESPONSABILIDADE' => 'S']);

        foreach ($ids as $id) {
            Cache::forget('patrimonio_id_' . $id);
        }
    }

    private function sanitizeText(?string $texto): string
    {
        if ($texto === null) {
            return '-';
        }

        $texto = trim($texto);
        if ($texto === '') {
            return '-';
        }

        $texto = str_replace(['&', '<', '>'], ['e', '', ''], $texto);

        if (mb_strlen($texto) > 250) {
            $texto = mb_substr($texto, 0, 247) . '...';
        }

        return $texto;
    }

    private function normalizarTrechoNomeArquivo(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return 'sem_identificacao';
        }

        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = preg_replace('/[^A-Za-z0-9]+/', '_', $texto) ?? $texto;
        $texto = trim($texto, '_');
        $texto = strtolower($texto);

        return $texto !== '' ? substr($texto, 0, 80) : 'sem_identificacao';
    }

    private function formatarNomeResponsavelParaTitulo(string $nome): string
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nome) ?? $nome);
        if ($nome === '') {
            return 'Responsavel';
        }

        $nome = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) ?: $nome;
        $partes = array_values(array_filter(explode(' ', $nome)));

        if (count($partes) >= 2) {
            $nome = $partes[0] . ' ' . $partes[count($partes) - 1];
        }

        return mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function formatarNomeProjetoParaTitulo(string $nomeProjeto): string
    {
        $nomeProjeto = trim(preg_replace('/\s+/', ' ', $nomeProjeto) ?? $nomeProjeto);
        if ($nomeProjeto === '') {
            return 'Projeto';
        }

        return mb_convert_case(mb_strtolower($nomeProjeto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function garantirNomeArquivoUnico(string $nomeArquivo, array &$nomesUsados, string $matricula): string
    {
        $chave = mb_strtolower($nomeArquivo, 'UTF-8');

        if (!isset($nomesUsados[$chave])) {
            $nomesUsados[$chave] = 1;
            return $nomeArquivo;
        }

        $nomesUsados[$chave]++;
        $base = pathinfo($nomeArquivo, PATHINFO_FILENAME);
        $extensao = pathinfo($nomeArquivo, PATHINFO_EXTENSION);

        return $base . ' ' . $matricula . '.' . $extensao;
    }

    private function responderErroLote(Request $request, string $mensagem, int $status): Response|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['message' => $mensagem], $status);
        }

        return back()->with($status >= 500 ? 'error' : 'warning', $mensagem);
    }

    private function resolverExecutavelRar(): string
    {
        $candidatos = [
            'C:\\Program Files\\WinRAR\\Rar.exe',
            'C:\\Program Files (x86)\\WinRAR\\Rar.exe',
        ];

        foreach ($candidatos as $candidato) {
            if (is_file($candidato)) {
                return $candidato;
            }
        }

        $process = new Process(['where', 'Rar.exe']);
        $process->setTimeout(10);
        $process->run();

        if ($process->isSuccessful()) {
            $linhas = preg_split('/\\r\\n|\\r|\\n/', trim($process->getOutput()));
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if ($linha !== '' && is_file($linha)) {
                    return $linha;
                }
            }
        }

        throw new \RuntimeException('O compactador RAR n?o est? dispon?vel no servidor.');
    }
}
