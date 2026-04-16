<?php

namespace App\Services;

use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use Illuminate\Support\Collection;

class PatrimonioLocalResolver
{
    public function resolve(Patrimonio $patrimonio): ?LocalProjeto
    {
        $cdlocal = $this->normalizeValue($patrimonio->CDLOCAL ?? null);
        if ($cdlocal === null) {
            return null;
        }

        $candidatos = LocalProjeto::with('projeto')
            ->where('cdlocal', $cdlocal)
            ->orderBy('id')
            ->get();

        return $this->resolveFromCandidates($patrimonio, $candidatos);
    }

    public function attach(Patrimonio $patrimonio): ?LocalProjeto
    {
        $local = $this->resolve($patrimonio);

        if ($local) {
            $patrimonio->setRelation('local', $local);
        }

        return $local;
    }

    public function attachMany(iterable $patrimonios): void
    {
        $items = $patrimonios instanceof Collection ? $patrimonios : collect($patrimonios);
        if ($items->isEmpty()) {
            return;
        }

        $cdLocais = $items
            ->map(fn ($patrimonio) => $patrimonio instanceof Patrimonio ? $this->normalizeValue($patrimonio->CDLOCAL ?? null) : null)
            ->filter(fn ($cdlocal) => $cdlocal !== null)
            ->unique()
            ->values();

        if ($cdLocais->isEmpty()) {
            return;
        }

        $locaisPorCodigo = LocalProjeto::with('projeto')
            ->whereIn('cdlocal', $cdLocais->all())
            ->orderBy('id')
            ->get()
            ->groupBy(fn (LocalProjeto $local) => (string) $local->cdlocal);

        foreach ($items as $patrimonio) {
            if (!$patrimonio instanceof Patrimonio) {
                continue;
            }

            $cdlocal = $this->normalizeValue($patrimonio->CDLOCAL ?? null);
            if ($cdlocal === null) {
                continue;
            }

            $local = $this->resolveFromCandidates($patrimonio, $locaisPorCodigo->get((string) $cdlocal, collect()));
            if ($local) {
                $patrimonio->setRelation('local', $local);
            }
        }
    }

    private function resolveFromCandidates(Patrimonio $patrimonio, Collection $candidatos): ?LocalProjeto
    {
        if ($candidatos->isEmpty()) {
            return null;
        }

        $cdProjeto = $this->normalizeValue($patrimonio->CDPROJETO ?? null);
        if ($cdProjeto !== null) {
            $localDoProjeto = $candidatos->first(function (LocalProjeto $local) use ($cdProjeto) {
                return $this->normalizeValue($local->projeto?->CDPROJETO ?? null) === $cdProjeto;
            });

            if ($localDoProjeto) {
                return $localDoProjeto;
            }
        }

        return $candidatos->first();
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
