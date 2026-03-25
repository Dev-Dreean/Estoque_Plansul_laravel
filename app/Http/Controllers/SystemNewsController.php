<?php

namespace App\Http\Controllers;

use App\Services\SystemNewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemNewsController extends Controller
{
    public function __construct(
        private readonly SystemNewsService $systemNewsService
    ) {
    }

    public function markAsSeen(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string', 'max:120'],
        ]);

        $this->systemNewsService->markAsSeen($user, $validated['keys']);

        return response()->json([
            'message' => 'Novidades registradas com sucesso.',
        ]);
    }
}
