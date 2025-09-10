<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use App\Models\Tabfant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $validated = $request->validate([
            'tipo_relatorio' => 'required|string|in:numero,descricao,aquisicao,cadastro,projeto,oc',
            'data_inicio_aquisicao' => 'nullable|required_if:tipo_relatorio,aquisicao|date',
            'data_fim_aquisicao' => 'nullable|required_if:tipo_relatorio,aquisicao|date|after_or_equal:data_inicio_aquisicao',
            'data_inicio_cadastro' => 'nullable|required_if:tipo_relatorio,cadastro|date',
            'data_fim_cadastro' => 'nullable|required_if:tipo_relatorio,cadastro|date|after_or_equal:data_inicio_cadastro',
            'projeto_busca' => 'nullable|string',
            'local_id' => 'nullable|required_if:tipo_relatorio,projeto|integer|exists:tabfant,id',
            'oc_busca' => 'nullable|required_if:tipo_relatorio,oc|string',
        ]);

        $query = Patrimonio::query()->with('usuario', 'local');

        switch ($validated['tipo_relatorio']) {
            case 'numero': $query->orderBy('NUPATRIMONIO'); break;
            case 'descricao': $query->orderBy('DEPATRIMONIO'); break;
            case 'aquisicao': $query->whereBetween('DTAQUISICAO', [$validated['data_inicio_aquisicao'], $validated['data_fim_aquisicao']]); break;
            case 'cadastro': $query->whereBetween('DTOPERACAO', [$validated['data_inicio_cadastro'], $validated['data_fim_cadastro']]); break;
            case 'projeto': $query->where('CDLOCAL', $validated['local_id']); break;
            case 'oc': $query->where('NUMOF', $validated['oc_busca']); break;
        }

        $resultados = $query->get();
        $filtros = $validated;

        return response()->json([
            'resultados' => $resultados,
            'filtros' => $filtros
        ]);
    }

    private function getQueryFromRequest(Request $request)
    {
        $validated = $request->validate([
            'tipo_relatorio' => 'required|string|in:numero,descricao,aquisicao,cadastro,projeto,oc',
            'data_inicio_aquisicao' => 'nullable|date', 'data_fim_aquisicao' => 'nullable|date',
            'data_inicio_cadastro' => 'nullable|date', 'data_fim_cadastro' => 'nullable|date',
            'projeto_busca' => 'nullable|string', 'local_id' => 'nullable|integer|exists:tabfant,id',
            'oc_busca' => 'nullable|string',
        ]);

        $query = Patrimonio::query()->with('usuario', 'local');
        switch ($validated['tipo_relatorio']) {
            case 'numero': $query->orderBy('NUPATRIMONIO'); break;
            case 'descricao': $query->orderBy('DEPATRIMONIO'); break;
            case 'aquisicao': $query->whereBetween('DTAQUISICAO', [$validated['data_inicio_aquisicao'], $validated['data_fim_aquisicao']]); break;
            case 'cadastro': $query->whereBetween('DTOPERACAO', [$validated['data_inicio_cadastro'], $validated['data_fim_cadastro']]); break;
            case 'projeto': $query->where('CDLOCAL', $validated['local_id']); break;
            case 'oc': $query->where('NUMOF', $validated['oc_busca']); break;
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
                'N° Patrimônio' => $patrimonio->NUPATRIMONIO, 'Descrição' => $patrimonio->DEPATRIMONIO, 'Situação' => $patrimonio->SITUACAO, 'Marca' => $patrimonio->MARCA, 'Modelo' => $patrimonio->MODELO, 'N° Série' => $patrimonio->NUSERIE, 'Cor' => $patrimonio->COR, 'Dimensão' => $patrimonio->DIMENSAO, 'Características' => $patrimonio->CARACTERISTICAS, 'Histórico' => $patrimonio->DEHISTORICO, 'Local (Nome)' => $patrimonio->local->LOCAL ?? 'N/A', 'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO, 'Projeto (Cód)' => $patrimonio->CDPROJETO, 'Data de Aquisição' => $patrimonio->DTAQUISICAO, 'Data de Baixa' => $patrimonio->DTBAIXA, 'Data de Garantia' => $patrimonio->DTGARANTIA, 'Cadastrado Por' => $patrimonio->usuario->NOMEUSER ?? 'N/A', 'Data de Cadastro' => $patrimonio->DTOPERACAO, 'OF' => $patrimonio->NUMOF, 'Cód. Objeto' => $patrimonio->CODOBJETO,
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
                'N° Patrimônio' => $patrimonio->NUPATRIMONIO, 'Descrição' => $patrimonio->DEPATRIMONIO, 'Situação' => $patrimonio->SITUACAO, 'Marca' => $patrimonio->MARCA, 'Modelo' => $patrimonio->MODELO, 'N° Série' => $patrimonio->NUSERIE, 'Cor' => $patrimonio->COR, 'Dimensão' => $patrimonio->DIMENSAO, 'Características' => $patrimonio->CARACTERISTICAS, 'Histórico' => $patrimonio->DEHISTORICO, 'Local (Nome)' => $patrimonio->local->LOCAL ?? 'N/A', 'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO, 'Projeto (Cód)' => $patrimonio->CDPROJETO, 'Data de Aquisição' => $patrimonio->DTAQUISICAO, 'Data de Baixa' => $patrimonio->DTBAIXA, 'Data de Garantia' => $patrimonio->DTGARANTIA, 'Cadastrado Por' => $patrimonio->usuario->NOMEUSER ?? 'N/A', 'Data de Cadastro' => $patrimonio->DTOPERACAO, 'OF' => $patrimonio->NUMOF, 'Cód. Objeto' => $patrimonio->CODOBJETO,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportarOds(Request $request)
    {
        $query = $this->getQueryFromRequest($request);
        $filePath = storage_path('app/temp_relatorio.ods');
        $writer = SimpleExcelWriter::create($filePath);

        foreach ($query->cursor() as $patrimonio) {
            $writer->addRow([
                'N° Patrimônio' => $patrimonio->NUPATRIMONIO, 'Descrição' => $patrimonio->DEPATRIMONIO, 'Situação' => $patrimonio->SITUACAO, 'Marca' => $patrimonio->MARCA, 'Modelo' => $patrimonio->MODELO, 'N° Série' => $patrimonio->NUSERIE, 'Cor' => $patrimonio->COR, 'Dimensão' => $patrimonio->DIMENSAO, 'Características' => $patrimonio->CARACTERISTICAS, 'Histórico' => $patrimonio->DEHISTORICO, 'Local (Nome)' => $patrimonio->local->LOCAL ?? 'N/A', 'Local Interno (Cód)' => $patrimonio->CDLOCALINTERNO, 'Projeto (Cód)' => $patrimonio->CDPROJETO, 'Data de Aquisição' => $patrimonio->DTAQUISICAO, 'Data de Baixa' => $patrimonio->DTBAIXA, 'Data de Garantia' => $patrimonio->DTGARANTIA, 'Cadastrado Por' => $patrimonio->usuario->NOMEUSER ?? 'N/A', 'Data de Cadastro' => $patrimonio->DTOPERACAO, 'OF' => $patrimonio->NUMOF, 'Cód. Objeto' => $patrimonio->CODOBJETO,
            ]);
        }
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function exportarPdf(Request $request)
    {
        $query = $this->getQueryFromRequest($request);
        $resultados = $query->get();
        $pdf = PDF::loadView('patrimonios.pdf', compact('resultados'));
        return $pdf->stream('relatorio_patrimonios.pdf');
    }
}