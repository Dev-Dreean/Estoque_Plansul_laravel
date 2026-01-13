<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class PatrimonioBulkController extends Controller
{
    public function import(Request $request)
    {
        $validated = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:csv,txt,xlsx,tsv'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('arquivo');
        $name = now()->format('Ymd_His') . '_' . Str::random(6) . '_' . $file->getClientOriginalName();
        $dir = storage_path('app/imports/bulk_update');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $name);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $name;

        if (!file_exists($fullPath)) {
            return response()->json([
                'ok' => false,
                'message' => 'Falha ao salvar a planilha. Tente novamente.',
            ], 422);
        }

        $exitCode = Artisan::call('patrimonios:bulk-update', [
            'file' => $fullPath,
            '--dry-run' => (bool) ($validated['dry_run'] ?? false),
        ]);

        $output = Artisan::output();

        return response()->json([
            'ok' => $exitCode === 0,
            'output' => $output,
        ]);
    }

    public function exportTemplate(Request $request)
    {
        $validated = $request->validate([
            'lista' => ['nullable', 'string'],
            'arquivo_lista' => ['nullable', 'file', 'mimes:csv,txt,xlsx,tsv'],
        ]);

        $numeros = $this->parseListaPatrimonios($validated['lista'] ?? '', $request->file('arquivo_lista'));

        if (empty($numeros)) {
            return response()->json([
                'ok' => false,
                'message' => 'Informe ao menos um numero de patrimonio.',
            ], 422);
        }

        $patrimonios = \App\Models\Patrimonio::whereIn('NUPATRIMONIO', $numeros)->get();
        $encontrados = $patrimonios->pluck('NUPATRIMONIO')->map(fn ($v) => (string) $v)->all();
        $faltando = array_values(array_diff(array_map('strval', $numeros), $encontrados));

        if (!empty($faltando)) {
            return response()->json([
                'ok' => false,
                'message' => 'Patrimonios nao encontrados: ' . implode(', ', $faltando),
            ], 422);
        }

        $rows = $patrimonios->map(function ($p) {
            return [
                'NUPATRIMONIO' => $p->NUPATRIMONIO,
                'CDLOCAL' => $p->CDLOCAL,
                'CDPROJETO' => $p->CDPROJETO,
                'DEPATRIMONIO' => $p->DEPATRIMONIO,
                'SITUACAO' => $p->SITUACAO,
                'MARCA' => $p->MARCA,
                'MODELO' => $p->MODELO,
                'NUSERIE' => $p->NUSERIE,
                'CDLOCALINTERNO' => $p->CDLOCALINTERNO,
                'DTAQUISICAO' => $p->DTAQUISICAO,
                'DTBAIXA' => $p->DTBAIXA,
                'DTGARANTIA' => $p->DTGARANTIA,
                'DEHISTORICO' => $p->DEHISTORICO,
                'NUMOF' => $p->NUMOF,
                'CODOBJETO' => $p->CODOBJETO,
                'NMPLANTA' => $p->NMPLANTA,
                'PESO' => $p->PESO,
                'TAMANHO' => $p->TAMANHO,
            ];
        })->values()->all();

        $dir = storage_path('app/temp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fileName = 'patrimonios_para_alteracao_' . now()->format('Ymd_His') . '.xlsx';
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;

        SimpleExcelWriter::create($path)->addRows($rows);

        return response()->download($path, $fileName)->deleteFileAfterSend(true);
    }

    private function parseListaPatrimonios(string $lista, $arquivo = null): array
    {
        $numeros = [];

        if ($arquivo) {
            $ext = strtolower((string) $arquivo->getClientOriginalExtension());
            if ($ext === 'txt') {
                $ext = 'csv';
            }
            $reader = SimpleExcelReader::create($arquivo->getPathname(), $ext);
            foreach ($reader->getRows() as $row) {
                $value = '';
                if (is_array($row)) {
                    $value = (string) (array_values($row)[0] ?? '');
                } else {
                    $value = (string) $row;
                }
                $numeros = array_merge($numeros, $this->extractNumeros($value));
            }
        }

        if (trim($lista) !== '') {
            $numeros = array_merge($numeros, $this->extractNumeros($lista));
        }

        $numeros = array_values(array_unique(array_filter($numeros)));

        return $numeros;
    }

    private function extractNumeros(string $value): array
    {
        $parts = preg_split('/[,\s;]+/', trim($value)) ?: [];
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^\d+$/', $part)) {
                $result[] = $part;
            }
        }
        return $result;
    }
}
