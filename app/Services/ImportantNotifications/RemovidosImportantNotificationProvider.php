<?php

namespace App\Services\ImportantNotifications;

use App\Contracts\ImportantNotificationProvider;
use App\Models\RegistroRemovido;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class RemovidosImportantNotificationProvider implements ImportantNotificationProvider
{
    public function notificationsFor(User $user): array
    {
        if (!$user->temAcessoTela(1009) || !Schema::hasTable('registros_removidos')) {
            return [];
        }

        $query = RegistroRemovido::query();
        $count = (int) $query->count();

        if ($count <= 0) {
            return [];
        }

        $latest = $query->latest('deleted_at')->first();
        $occurredAt = $latest?->deleted_at instanceof Carbon ? $latest->deleted_at : now();
        $titulo = $count === 1
            ? 'Você tem 1 item removido aguardando ação'
            : 'Você tem ' . $count . ' itens removidos aguardando ação';

        return [[
            'provider' => 'removidos_pendentes',
            'item_key' => 'removidos:pendentes',
            'modulo' => 'Removidos',
            'titulo' => $titulo,
            'descricao' => 'Há registros removidos aguardando restauração ou remoção definitiva.',
            'acao_label' => 'Abrir removidos',
            'url' => route('removidos.index'),
            'importance' => 80,
            'occurred_at' => $occurredAt->toIso8601String(),
            'occurred_at_label' => $occurredAt->format('d/m/Y H:i'),
            'countable' => true,
            'count_value' => $count,
        ]];
    }
}
