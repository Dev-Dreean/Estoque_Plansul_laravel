<?php

namespace App\Contracts;

use App\Models\User;

interface ImportantNotificationProvider
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function notificationsFor(User $user): array;
}
