<?php

namespace App\Services\ImportantNotifications;

use App\Contracts\ImportantNotificationProvider;
use App\Models\User;
use App\Services\SolicitacaoBemPendenciaService;

class SolicitacoesImportantNotificationProvider implements ImportantNotificationProvider
{
    public function __construct(
        private readonly SolicitacaoBemPendenciaService $pendenciaService
    ) {
    }

    public function notificationsFor(User $user): array
    {
        return $this->pendenciaService->notificationsFor($user);
    }
}
