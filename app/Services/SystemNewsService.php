<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemNewsService
{
    public function payloadForUser(User $user): array
    {
        $items = $this->activeItems();
        $seenKeys = $this->seenKeysForUser($user);

        $payloadItems = array_map(function (array $item) use ($seenKeys): array {
            $item['is_unseen'] = !in_array($item['key'], $seenKeys, true);

            return $item;
        }, $items);

        $unseenKeys = array_values(array_map(
            fn (array $item): string => $item['key'],
            array_filter($payloadItems, fn (array $item): bool => $item['is_unseen'])
        ));

        return [
            'items' => $payloadItems,
            'unseen_keys' => $unseenKeys,
            'unseen_count' => count($unseenKeys),
            'should_auto_open' => count($unseenKeys) > 0,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function markAsSeen(User $user, array $keys): void
    {
        if (!$this->isEnabled() || !Schema::hasTable('novidades_sistema_visualizacoes')) {
            return;
        }

        $validKeys = array_values(array_intersect($keys, array_column($this->activeItems(), 'key')));
        if ($validKeys === []) {
            return;
        }

        $now = now();

        $rows = array_map(fn (string $key): array => [
            'usuario_id' => $user->getAuthIdentifier(),
            'novidade_key' => $key,
            'visualizado_em' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $validKeys);

        DB::table('novidades_sistema_visualizacoes')->upsert(
            $rows,
            ['usuario_id', 'novidade_key'],
            ['visualizado_em', 'updated_at']
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeItems(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $items = [];

        foreach ((array) config('novidades.items', []) as $item) {
            $normalized = $this->normalizeItem((array) $item);
            if ($normalized === null) {
                continue;
            }

            $items[] = $normalized;
        }

        usort($items, function (array $left, array $right): int {
            return strcmp((string) $right['released_at'], (string) $left['released_at']);
        });

        return $items;
    }

    /**
     * @return array<int, string>
     */
    private function seenKeysForUser(User $user): array
    {
        if (!Schema::hasTable('novidades_sistema_visualizacoes')) {
            return [];
        }

        return DB::table('novidades_sistema_visualizacoes')
            ->where('usuario_id', $user->getAuthIdentifier())
            ->pluck('novidade_key')
            ->map(fn ($key) => (string) $key)
            ->all();
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private function normalizeItem(array $item): ?array
    {
        $key = trim((string) ($item['key'] ?? ''));
        if ($key === '' || ($item['active'] ?? true) !== true) {
            return null;
        }

        $releasedAt = $this->parseDate($item['released_at'] ?? null);
        if ($releasedAt === null || $releasedAt->isFuture()) {
            return null;
        }

        $details = array_values(array_filter(array_map(
            fn ($detail) => trim((string) $detail),
            (array) ($item['details'] ?? [])
        )));

        return [
            'key' => $key,
            'title' => trim((string) ($item['title'] ?? 'Novidade do sistema')),
            'summary' => trim((string) ($item['summary'] ?? '')),
            'highlight' => trim((string) ($item['highlight'] ?? '')),
            'details' => $details,
            'cta_label' => trim((string) ($item['cta_label'] ?? '')),
            'cta_url' => trim((string) ($item['cta_url'] ?? '')),
            'released_at' => $releasedAt->toIso8601String(),
            'released_at_label' => $releasedAt->format('d/m/Y'),
        ];
    }

    private function parseDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('novidades.enabled', true);
    }
}
