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

    public function search(Request $request){
        $response = $this->dashboardService->search($request);

        if($response['status']){
            return response()->json([
                'status' => true,
                'data' => $response['data'],
                'message' => '',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $response['error'],
            ]);
        }
    }
}
