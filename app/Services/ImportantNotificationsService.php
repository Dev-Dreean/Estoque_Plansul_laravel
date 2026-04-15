<?php

namespace App\Services;

use App\Models\User;
use App\Services\ImportantNotifications\RemovidosImportantNotificationProvider;
use App\Services\ImportantNotifications\SolicitacoesImportantNotificationProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ImportantNotificationsService
{
    private const PAYLOAD_CACHE_TTL_SECONDS = 60;

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
        return $this->payloadForUser($user)['items'];
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
        if (!(bool) config('notificacoes.enabled', true)) {
            return $this->emptyPayload();
        }

        return Cache::remember(
            $this->payloadCacheKey($user),
            now()->addSeconds(self::PAYLOAD_CACHE_TTL_SECONDS),
            fn () => $this->buildPayloadForUser($user)
        );
    }

    public function forgetUserPayload(User $user): void
    {
        Cache::forget($this->payloadCacheKey($user));
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

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function withToneClass(array $item): array
    {
        $provider = mb_strtolower(trim((string) ($item['provider'] ?? '')), 'UTF-8');
        $modulo = mb_strtolower(trim((string) ($item['modulo'] ?? '')), 'UTF-8');

        $item['tone_class'] = match (true) {
            $provider === 'removidos_pendentes', str_contains($modulo, 'removido') => 'important-notification-item--removidos',
            $provider === 'solicitacoes', str_contains($modulo, 'solicita') => 'important-notification-item--solicitacoes',
            default => 'important-notification-item--default',
        };

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayloadForUser(User $user): array
    {
        $items = collect([
            ...$this->solicitacoesProvider->notificationsFor($user),
            ...$this->removidosProvider->notificationsFor($user),
        ])
            ->filter(fn ($item) => is_array($item) && !empty($item['titulo']) && !empty($item['url']))
            ->map(fn (array $item) => $this->withToneClass($item))
            ->sortBy([
                ['importance', 'desc'],
                ['occurred_at', 'desc'],
                ['item_key', 'asc'],
            ])
            ->values()
            ->all();

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
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'items' => [],
            'total_count' => 0,
            'grouped' => [],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function payloadCacheKey(User $user): string
    {
        return 'important_notifications_payload:' . $user->getAuthIdentifier();
    }
}
