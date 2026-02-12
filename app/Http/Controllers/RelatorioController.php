<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use App\Models\Tabfant;
use App\Models\Funcionario;
use App\Models\LocalProjeto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;
// Removido uso de Maatwebsite\Excel; usaremos SimpleExcelWriter já presente
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RelatorioController extends Controller
{
    /**
     * (Não utilizado no momento, pois o formulário está em um modal)
     */
    public function create(): View
    {
        $locais = Tabfant::where('TIPO', 'LOCAL')
            ->select('id as codigo', 'LOCAL as descricao')
            ->orderBy('descricao')
            ->get();

        return view('relatorios.patrimonios.create', compact('locais'));
    }

    /**
     * Processa a solicitação do formulário do modal e retorna os resultados como JSON.
     */
    public function gerar(Request $request)
    {
        try {
            $this->normalizeSituacaoInput($request);

            if (!$request->filled('tipo_relatorio')) {
                $request->merge(['tipo_relatorio' => 'numero']);
            }

            // Validação base (não força campos condicionais aqui para permitir mensagens personalizadas)
            $base = $request->validate([
                'tipo_relatorio' => 'required|string|in:numero,descricao,aquisicao,cadastro,projeto,oc,uf,situacao',
                'numero_busca' => 'nullable|integer',
                'descricao_busca' => 'nullable|string',
                'sort_direction' => 'nullable|in:asc,desc',
                'projeto_busca' => 'nullable|string', // lista de códigos separados por vírgula
                'oc_busca' => 'nullable|string',
                'uf_busca' => 'nullable|string|size:2', // UF com 2 caracteres
                'situacao_busca' => 'nullable|array', // múltiplas situações
                'situacao_busca.*' => 'nullable|string',
                'voltagem' => 'nullable|string|max:50',
                'cdprojeto' => 'nullable|string',
                'cdlocal' => 'nullable|string',
                'conferido' => 'nullable|string|in:S,N,1,0',
                'data_inicio_aquisicao' => 'nullable|date',
                'data_fim_aquisicao' => 'nullable|date',
                'data_inicio_cadastro' => 'nullable|date',
                'data_fim_cadastro' => 'nullable|date',
            ]);

            $tipo = $base['tipo_relatorio'];

            // Validações condicionais manuais para respostas 422 claras
            $erros = [];
            if ($tipo === 'aquisicao') {
                if (!$request->filled('data_inicio_aquisicao') || !$request->filled('data_fim_aquisicao')) {
                    $erros['periodo'] = 'Informe Data Início e Data Fim de Aquisição.';
                } elseif ($request->date('data_fim_aquisicao') < $request->date('data_inicio_aquisicao')) {
                    $erros['periodo'] = 'Data fim não pode ser menor que data início (Aquisição).';
                }
            }
            if ($tipo === 'cadastro') {
                if (!$request->filled('data_inicio_cadastro') || !$request->filled('data_fim_cadastro')) {
                    $erros['periodo'] = 'Informe Data Início e Data Fim de Cadastro.';
                } elseif ($request->date('data_fim_cadastro') < $request->date('data_inicio_cadastro')) {
                    $erros['periodo'] = 'Data fim não pode ser menor que data início (Cadastro).';
                }
            }
            if ($tipo === 'oc' && !$request->filled('oc_busca')) {
                $erros['oc_busca'] = 'Informe o número da OC.';
            }
            if ($tipo === 'projeto' && !$request->filled('projeto_busca') && !$request->filled('cdprojeto')) {
                $erros['projeto_busca'] = 'Informe ao menos um código de projeto (lista ou filtro adicional).';
            }
            if ($tipo === 'numero' && $request->filled('numero_busca') && !is_numeric($request->input('numero_busca'))) {
                $erros['numero_busca'] = 'Número inválido.';
            }
            if ($tipo === 'uf' && !$request->filled('uf_busca')) {
                $erros['uf_busca'] = 'Informe a Unidade Federativa (UF).';
            }
            if ($tipo === 'situacao' && !$request->filled('situacao_busca')) {
                $erros['situacao_busca'] = 'Informe a Situação do Patrimônio.';
            }
            if ($erros) {
                return response()->json(['message' => 'Validação falhou', 'errors' => $erros], 422);
            }

            // Não carrega 'local.projeto' para tipo UF (será feito via JOIN)
            if ($tipo === 'uf') {
                $query = Patrimonio::query()->with('creator', 'projeto');
            } else {
                $query = Patrimonio::query()->with('creator', 'local.projeto');
            }

            $this->applyDefaultExcludeBaixa($query, $request);

            switch ($tipo) {
                case 'numero':
                    if ($request->filled('numero_busca')) {
                        $query->where('NUPATRIMONIO', $request->input('numero_busca'));
                    }
                    $query->orderBy('NUPATRIMONIO');
                    break;
                case 'descricao':
                    if ($request->filled('descricao_busca')) {
                        $dir = $request->input('sort_direction', 'asc');
                        $query->where('DEPATRIMONIO', 'like', '%' . $request->input('descricao_busca') . '%')
                            ->orderBy('DEPATRIMONIO', $dir === 'desc' ? 'desc' : 'asc');
                    } else {
                        $query->orderBy('DEPATRIMONIO');
                    }
                    break;
                case 'aquisicao':
                    $query->whereBetween('DTAQUISICAO', [$request->input('data_inicio_aquisicao'), $request->input('data_fim_aquisicao')]);
                    break;
                case 'cadastro':
                    $query->whereBetween('DTOPERACAO', [$request->input('data_inicio_cadastro'), $request->input('data_fim_cadastro')]);
                    break;
                case 'projeto':
                    $codes = collect(explode(',', $request->input('projeto_busca')))
                        ->map(fn($c) => trim($c))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    if ($codes) {
                        $query->whereIn('CDPROJETO', $codes);
                    }
                    break;
                case 'oc':
                    $query->where('NUMOF', $request->input('oc_busca'));
                    break;
                case 'uf':
                    // Filtra por UF através do LEFT JOIN (inclui registros sem projeto também)
                    $uf = strtoupper($request->input('uf_busca'));
                    $query->leftJoin('tabfant', 'patr.CDPROJETO', '=', 'tabfant.CDPROJETO')
                        ->where('tabfant.UF', $uf)
                        ->select('patr.*', 'tabfant.UF as projeto_uf')
                        ->distinct()
                        ->orderBy('patr.NUPATRIMONIO');
                    break;
                case 'situacao':
                    $this->applySituacaoFilter($query, $request->input('situacao_busca'));
                    $query->orderBy('NUPATRIMONIO');
                    break;
            }

            $this->applyExtraFilters($query, $request);

            $resultados = $query->get();
            return response()->json([
                'resultados' => $resultados,
                'filtros' => $request->only(array_keys($base))
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro interno ao gerar relatório',
                'exception' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function getQueryFromRequest(Request $request)
    {
        $this->normalizeSituacaoInput($request);

        if (!$request->filled('tipo_relatorio')) {
            $request->merge(['tipo_relatorio' => 'numero']);
        }
        $validated = $request->validate([
            'tipo_relatorio' => 'required|string|in:numero,descricao,aquisicao,cadastro,projeto,oc,uf,situacao',
            'data_inicio_aquisicao' => 'nullable|date',
            'data_fim_aquisicao' => 'nullable|date',
            'data_inicio_cadastro' => 'nullable|date',
            'data_fim_cadastro' => 'nullable|date',
            'projeto_busca' => 'nullable|string',
            'local_id' => 'nullable|integer|exists:tabfant,id',
            'cdprojeto' => 'nullable|string',
            'cdlocal' => 'nullable|string',
            'conferido' => 'nullable|string|in:S,N,1,0',
            'oc_busca' => 'nullable|string',
            'uf_busca' => 'nullable|string|size:2',
            'situacao_busca' => 'nullable|array', // múltiplas situações
            'situacao_busca.*' => 'nullable|string',
            'voltagem' => 'nullable|string|max:50',
            'numero_busca' => 'nullable|integer',
            'descricao_busca' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        // Não carrega 'local.projeto' para tipo UF (será feito via JOIN)
        if ($validated['tipo_relatorio'] === 'uf') {
            $query = Patrimonio::query()->with('creator', 'projeto');
        } else {
            $query = Patrimonio::query()->with('creator', 'local.projeto');
        }

        $this->applyDefaultExcludeBaixa($query, $request);

        switch ($validated['tipo_relatorio']) {
            case 'numero':
                if ($request->filled('numero_busca')) {
                    $query->where('NUPATRIMONIO', $request->input('numero_busca'));
                }
                $query->orderBy('NUPATRIMONIO');
                break;
            case 'descricao':
                if ($request->filled('descricao_busca')) {
                    $dir = $request->input('sort_direction', 'asc');
                    $query->where('DEPATRIMONIO', 'like', '%' . $request->input('descricao_busca') . '%')
                        ->orderBy('DEPATRIMONIO', $dir === 'desc' ? 'desc' : 'asc');
                } else {
                    $query->orderBy('DEPATRIMONIO');
                }
                break;
            case 'aquisicao':
                $query->whereBetween('DTAQUISICAO', [$validated['data_inicio_aquisicao'], $validated['data_fim_aquisicao']]);
                break;
            case 'cadastro':
                $query->whereBetween('DTOPERACAO', [$validated['data_inicio_cadastro'], $validated['data_fim_cadastro']]);
                break;
            case 'projeto':
                // Mantém compatibilidade: se veio local_id usa; se veio projeto_busca (lista) prioriza whereIn em CDPROJETO
                if ($request->filled('projeto_busca')) {
                    $codes = collect(explode(',', $request->input('projeto_busca')))
                        ->map(fn($c) => trim($c))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    if ($codes) {
                        $query->whereIn('CDPROJETO', $codes);
                    }
                } elseif ($request->filled('local_id')) {
                    $query->where('CDLOCAL', $validated['local_id']);
                }
                break;
            case 'oc':
                $query->where('NUMOF', $validated['oc_busca']);
                break;
            case 'uf':
                // Filtra por UF através do LEFT JOIN (inclui registros sem projeto também)
                $uf = strtoupper($request->input('uf_busca'));
                $query->leftJoin('tabfant', 'patr.CDPROJETO', '=', 'tabfant.CDPROJETO')
                    ->where('tabfant.UF', $uf)
                    ->select('patr.*', 'tabfant.UF as projeto_uf')
                    ->distinct()
                    ->orderBy('patr.NUPATRIMONIO');
                break;
            case 'situacao':
                $this->applySituacaoFilter($query, $request->input('situacao_busca'));
                $query->orderBy('NUPATRIMONIO');
                break;
        }
        $this->applyExtraFilters($query, $request);
        return $query;
    }

    private function applyExtraFilters($query, Request $request): void
    {
        if ($request->filled('cdprojeto')) {
            $val = trim((string) $request->input('cdprojeto'));
            if ($val !== '') {
                $locaisProjeto = collect();
                try {
                    $projeto = Tabfant::where('CDPROJETO', $val)->first(['id']);
                    if ($projeto) {
                        $locaisProjeto = LocalProjeto::where('tabfant_id', $projeto->id)
                            ->pluck('cdlocal');
                    }
                } catch (\Throwable $e) {
                    // Silencia falhas de lookup de locais sem quebrar o relatorio
                }

                $query->where(function ($q) use ($val, $locaisProjeto) {
                    $q->where('patr.CDPROJETO', $val);
                    if ($locaisProjeto->isNotEmpty()) {
                        $q->orWhereIn('patr.CDLOCAL', $locaisProjeto->all());
                    }
                });
            }
        }

        if ($request->filled('cdlocal')) {
            $val = trim((string) $request->input('cdlocal'));
            if ($val !== '') {
                $query->where('patr.CDLOCAL', $val);
            }
        }

        if ($request->filled('conferido')) {
            $val = strtoupper(trim((string) $request->input('conferido')));
            if ($val !== '') {
                $expr = "UPPER(COALESCE(NULLIF(TRIM(FLCONFERIDO), ''), 'N'))";
                $truthyDb = "('S','1','T','Y')";
                if (in_array($val, ['S', '1'], true)) {
                    $query->whereRaw("{$expr} IN {$truthyDb}");
                } elseif (in_array($val, ['N', '0'], true)) {
                    $query->whereRaw("{$expr} NOT IN {$truthyDb}");
                }
            }
        }

        if ($request->filled('situacao_busca') && $request->input('tipo_relatorio') !== 'situacao') {
            $this->applySituacaoFilter($query, $request->input('situacao_busca'));
        }

        if ($request->filled('voltagem')) {
            $val = strtoupper(trim((string) $request->input('voltagem')));
            if ($val !== '') {
                $like = '%' . $val . '%';
                $query->where(function ($q) use ($like) {
                    $q->whereRaw("UPPER(COALESCE(MODELO, '')) LIKE ?", [$like])
                        ->orWhereRaw("UPPER(COALESCE(CARACTERISTICAS, '')) LIKE ?", [$like])
                        ->orWhereRaw("UPPER(COALESCE(DEPATRIMONIO, '')) LIKE ?", [$like]);
                });
            }
        }
    }

    private function applySituacaoFilter($query, $raw): void
    {
        $situacoes = $this->normalizeSituacoes($raw);

        if (!$situacoes) {
            return;
        }

        $query->whereIn('SITUACAO', $situacoes);
    }

    private function applyDefaultExcludeBaixa($query, Request $request, string $field = 'situacao_busca'): void
    {
        $raw = $request->input($field);

        $values = collect(is_array($raw) ? $raw : ($raw !== null ? [$raw] : []))
            ->map(fn($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->values();

        if ($values->isNotEmpty()) {
            return;
        }

        try {
            if (Schema::hasColumn('patr', 'CDSITUACAO')) {
                $query->where(function ($q) {
                    $q->whereNull('CDSITUACAO')
                        ->orWhere('CDSITUACAO', '<>', 2);
                });
                return;
            }
        } catch (\Exception $e) {
            // MySQL antigo não suporta generation_expression, pular CDSITUACAO
        }

        try {
            if (Schema::hasColumn('patr', 'SITUACAO')) {
                $query->where(function ($q) {
                    $q->whereNull('SITUACAO')
                        ->orWhereRaw("UPPER(TRIM(SITUACAO)) NOT LIKE '%BAIXA%'");
                });
            }
        } catch (\Exception $e) {
            // MySQL antigo não suporta generation_expression, pular SITUACAO
        }
    }

    private function normalizeSituacoes($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        $map = [
            'A DISPOSICAO' => ['A DISPOSICAO', 'DISPONIVEL'],
            'DISPONIVEL' => ['A DISPOSICAO', 'DISPONIVEL'],
            'BAIXA' => ['BAIXA', 'BAIXADO'],
            'BAIXADO' => ['BAIXA', 'BAIXADO'],
            'MANUTENCAO' => ['MANUTENCAO', 'CONSERTO'],
            'CONSERTO' => ['MANUTENCAO', 'CONSERTO'],
        ];

        return collect($values)
            ->map(fn($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->flatMap(fn($value) => $map[$value] ?? [$value])
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeSituacaoInput(Request $request): void
    {
        if (!$request->has('situacao_busca')) {
            return;
        }

        $value = $request->input('situacao_busca');
        if (is_array($value)) {
            return;
        }

        if ($value === null || $value === '') {
            $request->merge(['situacao_busca' => []]);
            return;
        }

        $list = array_filter(array_map('trim', explode(',', (string) $value)));
        $request->merge(['situacao_busca' => $list]);
    }

    public function exportarExcel(Request $request)
    {
        $this->prepareExportRuntime();
        $query = $this->getQueryFromRequest($request);
        $query->setEagerLoads([]);
        $filePath = storage_path('app/temp_relatorio.xlsx');
        $writer = SimpleExcelWriter::create($filePath);
        $lookups = $this->getExportLookups();
        $localByCodigo = $lookups['locais'];
        $userByLogin = $lookups['usuarios'];

        foreach ($query->cursor() as $patrimonio) {
            $localNome = $patrimonio->CDLOCAL
                ? ($localByCodigo[$patrimonio->CDLOCAL] ?? 'N/A')
                : 'SISTEMA';
            $cadastradorNome = $patrimonio->USUARIO
                ? ($userByLogin[$patrimonio->USUARIO] ?? $patrimonio->USUARIO)
                : 'SISTEMA';

            $writer->addRow([
                'Nº Patrimônio' => $patrimonio->NUPATRIMONIO,
                'Descrição' => $patrimonio->DEPATRIMONIO,
                'Situação' => $patrimonio->SITUACAO,
                'Marca' => $patrimonio->MARCA,
                'Modelo' => $patrimonio->MODELO,
                'Nº Série' => $patrimonio->NUSERIE,
                'Cor' => $patrimonio->COR,
                'Dimensão' => $patrimonio->DIMENSAO,
                'Características' => $patrimonio->CARACTERISTICAS,
                'Histórico' => $patrimonio->DEHISTORICO,
                'Local (Nome)' => $localNome,
                'Local (Cód)' => $patrimonio->CDLOCAL,
                'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO,
                'Projeto (Cód)' => $patrimonio->CDPROJETO,
                'Data de Aquisição' => $patrimonio->DTAQUISICAO,
                'Data de Baixa' => $patrimonio->DTBAIXA,
                'Data de Garantia' => $patrimonio->DTGARANTIA,
                'Cadastrado por' => $cadastradorNome,
                'Data de Cadastro' => $patrimonio->DTOPERACAO,
                'OF' => $patrimonio->NUMOF,
                'Cód. Objeto' => $patrimonio->CODOBJETO,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportarCsv(Request $request)
    {
        $this->prepareExportRuntime();
        $query = $this->getQueryFromRequest($request);
        $query->setEagerLoads([]);
        $filePath = storage_path('app/temp_relatorio.csv');
        $writer = SimpleExcelWriter::create($filePath);
        $lookups = $this->getExportLookups();
        $localByCodigo = $lookups['locais'];
        $userByLogin = $lookups['usuarios'];

        foreach ($query->cursor() as $patrimonio) {
            $localNome = $patrimonio->CDLOCAL
                ? ($localByCodigo[$patrimonio->CDLOCAL] ?? 'N/A')
                : 'SISTEMA';
            $cadastradorNome = $patrimonio->USUARIO
                ? ($userByLogin[$patrimonio->USUARIO] ?? $patrimonio->USUARIO)
                : 'SISTEMA';

            $writer->addRow([
                'Nº Patrimônio' => $patrimonio->NUPATRIMONIO,
                'Descrição' => $patrimonio->DEPATRIMONIO,
                'Situação' => $patrimonio->SITUACAO,
                'Marca' => $patrimonio->MARCA,
                'Modelo' => $patrimonio->MODELO,
                'Nº Série' => $patrimonio->NUSERIE,
                'Cor' => $patrimonio->COR,
                'Dimensão' => $patrimonio->DIMENSAO,
                'Características' => $patrimonio->CARACTERISTICAS,
                'Histórico' => $patrimonio->DEHISTORICO,
                'Local (Nome)' => $localNome,
                'Local (Cód)' => $patrimonio->CDLOCAL,
                'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO,
                'Projeto (Cód)' => $patrimonio->CDPROJETO,
                'Data de Aquisição' => $patrimonio->DTAQUISICAO,
                'Data de Baixa' => $patrimonio->DTBAIXA,
                'Data de Garantia' => $patrimonio->DTGARANTIA,
                'Cadastrado por' => $cadastradorNome,
                'Data de Cadastro' => $patrimonio->DTOPERACAO,
                'OF' => $patrimonio->NUMOF,
                'Cód. Objeto' => $patrimonio->CODOBJETO,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportarOds(Request $request)
    {
        $this->prepareExportRuntime();
        $query = $this->getQueryFromRequest($request);
        $query->setEagerLoads([]);

        // Proteção simples contra PDFs gigantes (melhor experiência / evita timeout Dompdf)
        $total = (clone $query)->count();
        if ($total > 5000) {
            return back()->withErrors('O PDF excederia 5000 registros (' . $total . '). Refine os filtros antes de exportar.');
        }

        // Seleção básica de colunas (mantém leve). Pode ajustar conforme necessidade.
        $cols = [
            'NUPATRIMONIO',
            'DEPATRIMONIO',
            'SITUACAO',
            'MODELO',
            'MARCA',
            'NUSERIE',
            'CDPROJETO',
            'CDLOCAL',
            'NUMOF',
            'CODOBJETO',
            'DTAQUISICAO',
            'DTOPERACAO'
        ];
        $registros = $query->orderBy('NUPATRIMONIO')->get($cols);

        // Usa template simplificado já existente
        $modelo = 'simple';
        $pdf = PDF::loadView('relatorios.patrimonios.pdf-simples', [
            'modelo' => $modelo,
            'cols' => $cols,
            'registros' => $registros,
        ])->setPaper('a4', 'portrait');
        return $pdf->stream('relatorio_patrimonios.pdf');
    }

    private function prepareExportRuntime(): void
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        DB::disableQueryLog();
    }

    private function getExportLookups(): array
    {
        $locais = LocalProjeto::query()
            ->select(['cdlocal', 'delocal'])
            ->get()
            ->pluck('delocal', 'cdlocal')
            ->all();

        $usuarios = User::query()
            ->select(['NMLOGIN', 'NOMEUSER'])
            ->get()
            ->pluck('NOMEUSER', 'NMLOGIN')
            ->all();

        return [
            'locais' => $locais,
            'usuarios' => $usuarios,
        ];
    }

    public function exportarPdf(Request $request)
    {
        $this->prepareExportRuntime();
        $query = $this->getQueryFromRequest($request);
        $query->setEagerLoads([]);
        $resultados = $query->get();
        $pdf = PDF::loadView('patrimonios.pdf', compact('resultados'));
        return $pdf->stream('relatorio_patrimonios.pdf');
    }

    /**
     * Exporta lista completa de funcionários em Excel.
     * Colunas: Matrícula, Nome do Funcionário, Data de Admissão, Código do Cargo
     */
    public function exportarFuncionariosExcel()
    {
        $cols = ['CDMATRFUNCIONARIO', 'NMFUNCIONARIO', 'DTADMISSAO', 'CDCARGO'];
        $funcionarios = Funcionario::orderBy('NMFUNCIONARIO')->get($cols);

        $fileName = 'relatorio_funcionarios_' . now()->format('d-m-Y_His') . '.xlsx';
        $path = storage_path('app/' . $fileName);
        $writer = SimpleExcelWriter::create($path);

        // Escreve cada linha como array associativo — chaves viram cabeçalhos automaticamente
        foreach ($funcionarios as $f) {
            $writer->addRow([
                'Matrícula' => $f->CDMATRFUNCIONARIO,
                'Nome do Funcionário' => $f->NMFUNCIONARIO,
                'Data de Admissão' => $f->DTADMISSAO,
                'Código do Cargo' => $f->CDCARGO,
            ]);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * Nova rota unificada para geração e download de relatórios (PDF / XLSX / CSV) usando
     * os filtros já presentes na listagem principal de patrimônios. (Fluxo simplificado).
     *
     * Campos aceitos:
     * - modelo: 'simple' (default) | 'detailed'
     * - tipo_relatorio: 'pdf' | 'xlsx' | 'csv'
     * - filtros opcionais: nupatrimonio, cdprojeto, descricao, situacao, modelo_filtro, nmplanta, cadastrado_por
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'modelo' => 'nullable|in:simple,detailed',
            'tipo_relatorio' => 'required|in:pdf,xlsx,csv',
            'nupatrimonio' => 'nullable|string',
            'cdprojeto' => 'nullable|string',
            'descricao' => 'nullable|string',
            'situacao' => 'nullable|string',
            'modelo_filtro' => 'nullable|string',
            'nmplanta' => 'nullable|string',
            'cadastrado_por' => 'nullable|string',
        ]);

        $modelo = $validated['modelo'] ?? 'simple';

        $query = Patrimonio::query();

        // Relações úteis (usuário criador)
        $query->with('creator');

        $this->applyDefaultExcludeBaixa($query, $request, 'situacao');

        // Aplica filtros se presentes (ignora campos vazios / null)
        if (filled($validated['nupatrimonio'] ?? null)) {
            $query->where('NUPATRIMONIO', $validated['nupatrimonio']);
        }
        if (filled($validated['cdprojeto'] ?? null)) {
            $query->where('CDPROJETO', $validated['cdprojeto']);
        }
        if (filled($validated['descricao'] ?? null)) {
            $query->where('DEPATRIMONIO', 'like', '%' . $validated['descricao'] . '%');
        }
        if (filled($validated['situacao'] ?? null)) {
            $query->where('SITUACAO', 'like', '%' . $validated['situacao'] . '%');
        }
        if (filled($validated['modelo_filtro'] ?? null)) {
            $query->where('MODELO', 'like', '%' . $validated['modelo_filtro'] . '%');
        }
        if (filled($validated['nmplanta'] ?? null)) {
            $query->where('NMPLANTA', $validated['nmplanta']);
        }
        if (filled($validated['cadastrado_por'] ?? null)) {
            $query->where('CDMATRFUNCIONARIO', $validated['cadastrado_por']);
        }

        // Colunas (ajuste se quiser mais / menos no modo simple vs detailed)
        $colsSimple = [
            'NUPATRIMONIO',
            'CODOBJETO',
            'CDPROJETO',
            'CDLOCAL',
            'MODELO',
            'DEPATRIMONIO',
            'SITUACAO',
            'DTAQUISICAO',
            'DTOPERACAO',
            'NMPLANTA'
        ];
        $colsDetailed = [
            'NUPATRIMONIO',
            'DEPATRIMONIO',
            'SITUACAO',
            'MARCA',
            'MODELO',
            'NUSERIE',
            'COR',
            'DIMENSAO',
            'CARACTERISTICAS',
            'DEHISTORICO',
            'CDPROJETO',
            'CDLOCAL',
            'NUMOF',
            'CODOBJETO',
            'DTAQUISICAO',
            'DTOPERACAO',
            'DTGARANTIA',
            'DTBAIXA',
            'NMPLANTA'
        ];

        $cols = $modelo === 'detailed' ? $colsDetailed : $colsSimple;

        $registros = $query->orderBy('NUPATRIMONIO')->get($cols);

        // Se não houver registros, ainda assim gerar saída vazia coerente.
        $tipo = $validated['tipo_relatorio'];

        if ($tipo === 'pdf') {
            $pdf = Pdf::loadView('relatorios.patrimonios.pdf-simples', [
                'modelo' => $modelo,
                'cols' => $cols,
                'registros' => $registros,
            ])->setPaper('a4', 'portrait');
            return $pdf->stream('patrimonios_' . $modelo . '_' . now()->format('Ymd_His') . '.pdf');
        }

        // Gera planilha (xlsx/csv)
        $ext = $tipo === 'xlsx' ? 'xlsx' : 'csv';
        $fileName = 'patrimonios_' . $modelo . '_' . now()->format('Ymd_His') . '.' . $ext;
        $tempPath = storage_path('app/' . $fileName);
        $writer = SimpleExcelWriter::create($tempPath);

        foreach ($registros as $r) {
            $row = [];
            foreach ($cols as $c) {
                $valor = $r->$c;
                if ($valor instanceof \DateTimeInterface) {
                    $valor = $valor->format('Y-m-d');
                }
                $row[$c] = $valor;
            }
            // Acrescenta nome do usuário se carregado (apenas detailed?)
            if ($modelo === 'detailed') {
                $row['USUARIO'] = $r->creator->NOMEUSER ?? null;
            }
            $writer->addRow($row);
        }

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
