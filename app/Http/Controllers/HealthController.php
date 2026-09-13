<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Safe availability + DB connectivity. Never returns secrets.
     */
    public function __invoke(): JsonResponse
    {
        $dbOk = false;
        $dbError = null;

        try {
            DB::select('select 1 as ok');
            $dbOk = true;
        } catch (Throwable $e) {
            $dbError = 'database_unavailable';
            report($e);
        }

        $status = $dbOk ? 200 : 503;

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'env' => config('app.env'),
            'time' => now()->toIso8601String(),
            'checks' => [
                'database' => $dbOk ? 'ok' : 'fail',
            ],
            'error' => $dbError,
        ], $status);
    }
}
