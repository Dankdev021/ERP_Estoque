<?php

namespace App\Http\Controllers\Api;

use App\Domain\Dashboard\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function resumo(): JsonResponse
    {
        return response()->json(app(DashboardService::class)->resumo());
    }
}
