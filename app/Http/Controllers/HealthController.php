<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): Response|JsonResponse
    {
        if (request()->boolean('db')) {
            try {
                DB::connection()->getPdo();
                $cities = DB::table('cities')->count();

                return response()->json([
                    'status' => 'ok',
                    'database' => 'connected',
                    'cities' => $cities,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 'error',
                    'database' => $e->getMessage(),
                ], 503);
            }
        }

        return response('OK', 200);
    }
}
