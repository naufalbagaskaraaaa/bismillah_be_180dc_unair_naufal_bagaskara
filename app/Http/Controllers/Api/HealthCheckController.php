<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
                'services' => [
                    'database' => 'connected',
                    'app' => 'up'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'timestamp' => now()->toIso8601String(),
                'services' => [
                    'database' => 'disconnected',
                    'reason' => $e->getMessage()
                ]
            ], 503);
        }
    }
}
