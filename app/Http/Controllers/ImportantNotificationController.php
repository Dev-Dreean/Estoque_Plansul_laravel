<?php

namespace App\Http\Controllers;

use App\Services\ImportantNotificationsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportantNotificationController extends Controller
{
    public function __construct(
        private readonly ImportantNotificationsService $importantNotificationsService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json(
            $this->importantNotificationsService->payloadForUser($user)
        );
    }
}
