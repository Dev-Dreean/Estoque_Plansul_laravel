<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SyncRemoteController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $table = strtolower((string) $request->input('table', 'all'));
        $dryRun = filter_var($request->input('dry_run', false), FILTER_VALIDATE_BOOLEAN);
        $chunk = (int) $request->input('chunk', 500);
        $chunk = $chunk > 0 ? $chunk : 500;

        $exitCode = Artisan::call('sync:remote', [
            'table' => $table,
            '--dry-run' => $dryRun,
            '--chunk' => $chunk,
        ]);

        return response()->json([
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
        ]);
    }
}
