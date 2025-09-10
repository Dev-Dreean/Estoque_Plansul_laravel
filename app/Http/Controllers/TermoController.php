<?php

namespace App\Http\Controllers;

use App\Models\Patrimonio;
use Illuminate\Http\Request;
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

        // Sintaxe do redirect corrigida e apontando para o lugar certo
        return redirect()->route('patrimonios.index')
                        ->with('success', "Código de Termo Nº {$novoCodTermo} gerado e atribuído com sucesso a ". count($validated['patrimonio_ids']) ." itens!");
    }

    /**
     * Gera a planilha Excel de um termo existente.
     */
    public function exportarExcel(Request $request)
    {
        $validated = $request->validate(['cod_termo' => 'required|integer']);
        $codTermo = $validated['cod_termo'];
        $query = Patrimonio::query()->where('NMPLANTA', $codTermo);

        if ($query->count() === 0) {
            return back()->with('error', 'Nenhum patrimônio encontrado para o Cód. Termo informado.');
        }

        $filePath = storage_path("app/termo_de_transferencia_{$codTermo}.xlsx");
        $writer = SimpleExcelWriter::create($filePath);

        $writer->addRow(["TERMO DE TRANSFERÊNCIA Nº", $codTermo]);
        $writer->addRow([]);
        $writer->addRow(['Projeto Nº', 'Patr.', 'Descrição']);

        foreach ($query->cursor() as $patrimonio) {
            $writer->addRow([
                'Projeto Nº' => $patrimonio->CDPROJETO,
                'Patr.' => $patrimonio->NUPATRIMONIO,
                'Descrição' => $patrimonio->DEPATRIMONIO,
            ]);
        }

        $writer->addRow([]);
        $writer->addRow(['--- FIM ---']);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}