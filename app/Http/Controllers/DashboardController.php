<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function search(): JsonResponse
    {
        $result = $this->dashboardService->search();
        return $this->formatResponse($result);
    }

    public function userGraph(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|in:all,monthly'
        ]);

        $result = $this->dashboardService->userGraph($request);
        return $this->formatResponse($result);
    }

    private function formatResponse(array $result): JsonResponse
    {
        return response()->json([
            'status' => $result['status'],
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? null,
            'error' => $result['error'] ?? null,
        ]);
    }
}
