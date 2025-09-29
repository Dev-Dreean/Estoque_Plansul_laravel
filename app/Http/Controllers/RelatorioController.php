<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use App\Models\Tabfant;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;
// Removido uso de Maatwebsite\Excel; usaremos SimpleExcelWriter já presente
use Illuminate\Support\Facades\Auth;

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
            if (!$request->filled('tipo_relatorio')) {
                $request->merge(['tipo_relatorio' => 'numero']);
            }

            // Validação base (não força campos condicionais aqui para permitir mensagens personalizadas)
            $base = $request->validate([
                'tipo_relatorio' => 'required|string|in:numero,descricao,aquisicao,cadastro,projeto,oc',
                'numero_busca' => 'nullable|integer',
                'descricao_busca' => 'nullable|string',
                'sort_direction' => 'nullable|in:asc,desc',
                'projeto_busca' => 'nullable|string', // lista de códigos separados por vírgula
                'oc_busca' => 'nullable|string',
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
            if ($tipo === 'projeto' && !$request->filled('projeto_busca')) {
                $erros['projeto_busca'] = 'Informe ao menos um código de projeto.';
            }
            if ($tipo === 'numero' && $request->filled('numero_busca') && !is_numeric($request->input('numero_busca'))) {
                $erros['numero_busca'] = 'Número inválido.';
            }
            if ($erros) {
                return response()->json(['message' => 'Validação falhou', 'errors' => $erros], 422);
            }

            // 'usuario' não existe mais no Model; relação correta é 'creator' (quem cadastrou)
            $query = Patrimonio::query()->with('creator', 'local');
            // Segurança: usuários não-ADM veem registros que criaram OU dos quais são responsáveis
            $user = Auth::user();
            if ($user && ($user->PERFIL ?? null) !== 'ADM') {
                $nmLogin = (string) ($user->NMLOGIN ?? '');
                $nmUser  = (string) ($user->NOMEUSER ?? '');
                $query->where(function ($q) use ($user, $nmLogin, $nmUser) {
                    $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                        ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                        ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser]);
                });
            }
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
            }

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
        if (!$request->filled('tipo_relatorio')) {
            $request->merge(['tipo_relatorio' => 'numero']);
        }
        $validated = $request->validate([
            'tipo_relatorio' => 'required|string|in:numero,descricao,aquisicao,cadastro,projeto,oc',
            'data_inicio_aquisicao' => 'nullable|date',
            'data_fim_aquisicao' => 'nullable|date',
            'data_inicio_cadastro' => 'nullable|date',
            'data_fim_cadastro' => 'nullable|date',
            'projeto_busca' => 'nullable|string',
            'local_id' => 'nullable|integer|exists:tabfant,id',
            'oc_busca' => 'nullable|string',
            'numero_busca' => 'nullable|integer',
            'descricao_busca' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc'
        ]);

        // Ajuste de relações: usar 'creator' (usuário do sistema que cadastrou) e 'local'
        $query = Patrimonio::query()->with('creator', 'local');
        // Segurança: usuários não-ADM veem registros que criaram OU dos quais são responsáveis
        $user = Auth::user();
        if ($user && ($user->PERFIL ?? null) !== 'ADM') {
            $nmLogin = (string) ($user->NMLOGIN ?? '');
            $nmUser  = (string) ($user->NOMEUSER ?? '');
            $query->where(function ($q) use ($user, $nmLogin, $nmUser) {
                $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser]);
            });
        }
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
                } else {
                    $query->where('CDLOCAL', $validated['local_id']);
                }
                break;
            case 'oc':
                $query->where('NUMOF', $validated['oc_busca']);
                break;
        }
        return $query;
    }

    public function exportarExcel(Request $request)
    {
        $query = $this->getQueryFromRequest($request);
        $filePath = storage_path('app/temp_relatorio.xlsx');
        $writer = SimpleExcelWriter::create($filePath);

        foreach ($query->cursor() as $patrimonio) {
            $writer->addRow([
                'N° Patrimônio' => $patrimonio->NUPATRIMONIO,
                'Descrição' => $patrimonio->DEPATRIMONIO,
                'Situação' => $patrimonio->SITUACAO,
                'Marca' => $patrimonio->MARCA,
                'Modelo' => $patrimonio->MODELO,
                'N° Série' => $patrimonio->NUSERIE,
                'Cor' => $patrimonio->COR,
                'Dimensão' => $patrimonio->DIMENSAO,
                'Características' => $patrimonio->CARACTERISTICAS,
                'Histórico' => $patrimonio->DEHISTORICO,
                'Local (Nome)' => $patrimonio->local->LOCAL ?? 'SISTEMA',
                'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO,
                'Projeto (Cód)' => $patrimonio->CDPROJETO,
                'Data de Aquisição' => $patrimonio->DTAQUISICAO,
                'Data de Baixa' => $patrimonio->DTBAIXA,
                'Data de Garantia' => $patrimonio->DTGARANTIA,
                'Cadastrado Por' => $patrimonio->creator->NOMEUSER ?? 'SISTEMA',
                'Data de Cadastro' => $patrimonio->DTOPERACAO,
                'OF' => $patrimonio->NUMOF,
                'Cód. Objeto' => $patrimonio->CODOBJETO,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportarCsv(Request $request)
    {
        $query = $this->getQueryFromRequest($request);
        $filePath = storage_path('app/temp_relatorio.csv');
        $writer = SimpleExcelWriter::create($filePath);

        foreach ($query->cursor() as $patrimonio) {
            $writer->addRow([
                'N° Patrimônio' => $patrimonio->NUPATRIMONIO,
                'Descrição' => $patrimonio->DEPATRIMONIO,
                'Situação' => $patrimonio->SITUACAO,
                'Marca' => $patrimonio->MARCA,
                'Modelo' => $patrimonio->MODELO,
                'N° Série' => $patrimonio->NUSERIE,
                'Cor' => $patrimonio->COR,
                'Dimensão' => $patrimonio->DIMENSAO,
                'Características' => $patrimonio->CARACTERISTICAS,
                'Histórico' => $patrimonio->DEHISTORICO,
                'Local (Nome)' => $patrimonio->local->LOCAL ?? 'N/A',
                'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO,
                'Projeto (Cód)' => $patrimonio->CDPROJETO,
                'Data de Aquisição' => $patrimonio->DTAQUISICAO,
                'Data de Baixa' => $patrimonio->DTBAIXA,
                'Data de Garantia' => $patrimonio->DTGARANTIA,
                'Cadastrado Por' => $patrimonio->creator->NOMEUSER ?? 'N/A',
                'Data de Cadastro' => $patrimonio->DTOPERACAO,
                'OF' => $patrimonio->NUMOF,
                'Cód. Objeto' => $patrimonio->CODOBJETO,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportarOds(Request $request)
    {
        $query = $this->getQueryFromRequest($request);

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

    public function exportarPdf(Request $request)
    {
        $query = $this->getQueryFromRequest($request);
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

        // Cabeçalhos
        $writer->addRow([
            'Matrícula',
            'Nome do Funcionário',
            'Data de Admissão',
            'Código do Cargo'
        ]);

        foreach ($funcionarios as $f) {
            $writer->addRow([
                $f->CDMATRFUNCIONARIO,
                $f->NMFUNCIONARIO,
                $f->DTADMISSAO,
                $f->CDCARGO,
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
