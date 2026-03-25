<?php

namespace App\Services;

use App\Models\User;
use App\Services\ImportantNotifications\RemovidosImportantNotificationProvider;
use App\Services\ImportantNotifications\SolicitacoesImportantNotificationProvider;
use Illuminate\Support\Collection;

class ImportantNotificationsService
{
    public function __construct(
        private readonly SolicitacoesImportantNotificationProvider $solicitacoesProvider,
        private readonly RemovidosImportantNotificationProvider $removidosProvider
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        if (!(bool) config('notificacoes.enabled', true)) {
            return [];
        }

        return collect([
            ...$this->solicitacoesProvider->notificationsFor($user),
            ...$this->removidosProvider->notificationsFor($user),
        ])
            ->filter(fn ($item) => is_array($item) && !empty($item['titulo']) && !empty($item['url']))
            ->sortBy([
                ['importance', 'desc'],
                ['occurred_at', 'desc'],
                ['item_key', 'asc'],
            ])
            ->values()
            ->all();
    }

    public function totalCountForUser(User $user): int
    {
        return $this->countItems($this->forUser($user));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupedByModuleForUser(User $user): array
    {
        return collect($this->forUser($user))
            ->groupBy(fn (array $item) => (string) ($item['modulo'] ?? 'Outros'))
            ->map(fn (Collection $items) => $items->values()->all())
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadForUser(User $user): array
    {
        $items = $this->forUser($user);

        return [
            'items' => $items,
            'total_count' => $this->countItems($items),
            'grouped' => collect($items)
                ->groupBy(fn (array $item) => (string) ($item['modulo'] ?? 'Outros'))
                ->map(fn (Collection $group) => $group->values()->all())
                ->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function countItems(array $items): int
    {
        return (int) collect($items)
            ->filter(fn (array $item) => (bool) ($item['countable'] ?? false))
            ->sum(fn (array $item) => max(1, (int) ($item['count_value'] ?? 1)));
    }
}
