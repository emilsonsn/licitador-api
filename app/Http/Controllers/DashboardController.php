<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private $dashboardService;

    public function __construct(DashboardService $dashboardService) {
        $this->dashboardService = $dashboardService;
    }

    public function search(){
        $result = $this->dashboardService->search();

        $this->response($result);
    }


    public function userGraph(Request $request){
        $result = $this->dashboardService->userGraph($request);
        
        $this->response($result);
    }

    private function response($result){
        return response()->json([
            'status' => $result['status'],
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? null,
            'error' => $result['error'] ?? null,
        ]);
    }
}
