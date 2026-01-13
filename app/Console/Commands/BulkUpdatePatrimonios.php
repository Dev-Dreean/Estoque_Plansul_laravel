<?php

namespace App\Console\Commands;

use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use App\Models\Tabfant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\SimpleExcel\SimpleExcelReader;

class BulkUpdatePatrimonios extends Command
{
    protected $signature = 'patrimonios:bulk-update
                            {file : Caminho do arquivo CSV/TSV/XLSX}
                            {--dry-run : Simular sem salvar no banco}
                            {--delimiter= : Delimitador CSV/TSV (opcional)}';

    protected $description = 'Atualiza patrimonios em massa a partir de planilha (CSV/TSV/XLSX)';

    public function handle(): int
    {
        $filePath = (string) $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');
        $delimiter = $this->option('delimiter');

        if (!file_exists($filePath)) {
            $this->error("Arquivo nao encontrado: {$filePath}");
            return 1;
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $reader = SimpleExcelReader::create($filePath);
        if (in_array($ext, ['csv', 'tsv'], true)) {
            $delimiter = $delimiter ?: $this->detectDelimiter($filePath, $ext);
            if ($delimiter) {
                $reader->useDelimiter($delimiter);
            }
        }

        $allowedUpdates = $this->allowedUpdateColumns();

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $changes = [];

        foreach ($reader->getRows() as $row) {
            $processed++;
            $normalized = $this->normalizeRow($row);

            $nuseqpatr = $normalized['NUSEQPATR'] ?? null;
            $nupatrimonio = $normalized['NUPATRIMONIO'] ?? null;

            if (empty($nuseqpatr) && empty($nupatrimonio)) {
                $errors++;
                $this->warn("Linha {$processed}: NUSEQPATR ou NUPATRIMONIO obrigatorio.");
                continue;
            }

            $patrimonio = null;
            if (!empty($nuseqpatr)) {
                $patrimonio = Patrimonio::find($nuseqpatr);
            } elseif (!empty($nupatrimonio)) {
                $patrimonio = Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->first();
            }

            if (!$patrimonio) {
                $errors++;
                $this->warn("Linha {$processed}: patrimonio nao encontrado.");
                continue;
            }

            $updates = $this->buildUpdates($normalized, $allowedUpdates);
            if (empty($updates)) {
                $skipped++;
                continue;
            }

            $validationError = $this->validateLocalProjeto($updates);
            if ($validationError) {
                $errors++;
                $this->warn("Linha {$processed}: {$validationError}");
                continue;
            }

            $patrimonio->fill($updates);
            if (!$patrimonio->isDirty()) {
                $skipped++;
                continue;
            }

            $dirty = $patrimonio->getDirty();
            $before = [];
            foreach (array_keys($dirty) as $field) {
                $before[$field] = $patrimonio->getOriginal($field);
            }

            if ($dryRun) {
                $this->line("Linha {$processed}: [DRY-RUN] {$patrimonio->NUPATRIMONIO} atualizado.");
            } else {
                $patrimonio->save();
            }

            $updated++;
            $changes[] = [
                'NUSEQPATR' => $patrimonio->NUSEQPATR,
                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
                'before' => $before,
                'after' => $dirty,
            ];
        }

        if (!$dryRun && !empty($changes)) {
            $this->saveBackup($changes);
        }

        $this->newLine();
        $this->line("Processados: {$processed}");
        $this->line("Atualizados: {$updated}");
        $this->line("Ignorados: {$skipped}");
        $this->line("Erros: {$errors}");

        if ($dryRun) {
            $this->warn('Dry-run ativo: nenhuma alteracao foi salva.');
        }

        return $errors > 0 ? 1 : 0;
    }

    private function allowedUpdateColumns(): array
    {
        return [
            'SITUACAO' => 'SITUACAO',
            'TIPO' => 'TIPO',
            'MARCA' => 'MARCA',
            'MODELO' => 'MODELO',
            'CARACTERISTICAS' => 'CARACTERISTICAS',
            'DIMENSAO' => 'DIMENSAO',
            'COR' => 'COR',
            'NUSERIE' => 'NUSERIE',
            'CDLOCAL' => 'CDLOCAL',
            'DTAQUISICAO' => 'DTAQUISICAO',
            'DTBAIXA' => 'DTBAIXA',
            'DTGARANTIA' => 'DTGARANTIA',
            'DEHISTORICO' => 'DEHISTORICO',
            'DTLAUDO' => 'DTLAUDO',
            'DEPATRIMONIO' => 'DEPATRIMONIO',
            'CDMATRFUNCIONARIO' => 'CDMATRFUNCIONARIO',
            'CDLOCALINTERNO' => 'CDLOCALINTERNO',
            'CDPROJETO' => 'CDPROJETO',
            'USUARIO' => 'USUARIO',
            'DTOPERACAO' => 'DTOPERACAO',
            'FLCONFERIDO' => 'FLCONFERIDO',
            'NUMOF' => 'NUMOF',
            'CODOBJETO' => 'CODOBJETO',
            'NMPLANTA' => 'NMPLANTA',
            'PESO' => 'PESO',
            'TAMANHO' => 'TAMANHO',
        ];
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $cleanKey = $this->normalizeHeader($key);
            $normalized[$cleanKey] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtoupper(trim($header));
        $header = str_replace(' ', '_', $header);
        $header = preg_replace('/[^A-Z0-9_]/', '', $header) ?: '';

        return $header;
    }

    private function buildUpdates(array $normalized, array $allowedUpdates): array
    {
        $updates = [];

        foreach ($allowedUpdates as $column => $field) {
            if (!array_key_exists($column, $normalized)) {
                continue;
            }

            $raw = $normalized[$column];

            if ($raw === '' || $raw === null) {
                continue;
            }

            if (is_string($raw) && strtoupper(trim($raw)) === 'NULL') {
                $updates[$field] = null;
                continue;
            }

            $updates[$field] = $this->castValue($column, $raw);
        }

        return $updates;
    }

    private function castValue(string $column, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (in_array($column, ['DTAQUISICAO', 'DTBAIXA', 'DTGARANTIA', 'DTLAUDO', 'DTOPERACAO'], true)) {
            return $this->parseDate($value);
        }

        if ($column === 'FLCONFERIDO') {
            return strtoupper((string) $value);
        }

        return $value;
    }

    private function parseDate(mixed $value): mixed
    {
        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function validateLocalProjeto(array &$updates): ?string
    {
        if (isset($updates['CDPROJETO']) && !isset($updates['CDLOCAL'])) {
            return 'CDPROJETO exige CDLOCAL na mesma linha.';
        }

        $local = null;
        if (isset($updates['CDLOCAL'])) {
            $local = LocalProjeto::with('projeto')->where('cdlocal', $updates['CDLOCAL'])->first();
            if (!$local) {
                return 'CDLOCAL nao existe.';
            }
        }

        $projeto = null;
        if (isset($updates['CDPROJETO'])) {
            $projeto = Tabfant::where('CDPROJETO', $updates['CDPROJETO'])->first();
            if (!$projeto) {
                return 'CDPROJETO nao existe.';
            }
        }

        if ($local && $projeto) {
            if ((int) $local->tabfant_id !== (int) $projeto->id) {
                return 'CDLOCAL nao pertence ao CDPROJETO informado.';
            }
        }

        if ($local && !$projeto) {
            if (!$local->projeto) {
                return 'CDLOCAL sem projeto vinculado.';
            }

            $updates['CDPROJETO'] = $local->projeto->CDPROJETO;
        }

        if (isset($updates['FLCONFERIDO']) && !in_array($updates['FLCONFERIDO'], ['S', 'N'], true)) {
            return 'FLCONFERIDO deve ser S ou N.';
        }

        return null;
    }

    private function saveBackup(array $changes): void
    {
        $dir = storage_path('backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'patrimonios_bulk_update_' . now()->format('Y_m_d_His') . '.json';
        file_put_contents($path, json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("Backup: {$path}");
    }

    private function detectDelimiter(string $filePath, string $ext): ?string
    {
        if ($ext === 'tsv') {
            return "\t";
        }

        $line = '';
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $line = fgets($handle) ?: '';
            fclose($handle);
        }

        $candidates = [',', ';', "\t"];
        $best = null;
        $bestCount = 0;

        foreach ($candidates as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $best;
    }
}
