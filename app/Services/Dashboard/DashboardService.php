<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Exception;
use App\Models\Tender;

class DashboardService
{

    public function search()
    {
        try {
            $totalUsers = User::where('is_admin', false)->count();
            $totalMonthUsers = User::where('is_admin', false)
                ->whereMonth('created_at', now()->month)
                ->count();

            $totalActiveUsers = User::where('is_admin', false)
                ->where('is_active', true)
                ->count();
            
            $totalInactiveUsers = User::where('is_admin', false)
                ->where('is_active', false)
                ->count();


            $totalTenders = Tender::count();
            $totalMonthTenders = Tender::whereMonth('created_at', now()->month)
                ->count();

            $data = [
                'totalUsers' => $totalUsers,
                'totalMonthUsers' => $totalMonthUsers,
                
                'totalActiveUsers' => $totalActiveUsers,
                'totalInactiveUsers' => $totalInactiveUsers,

                'totalTenders' => $totalTenders,
                'totalMonthTenders' => $totalMonthTenders,
            ];

            return ['status' => true, 'data' => $data];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function userGraph($request)
    {
        try {
            $users = User::where('is_admin', false);
            $period = $request->period ?? 'monthly'; 

            switch ($period) {
                case 'all':
                    break;
                case 'monthly':
                    $users->whereMonth('created_at', now()->month);
                    break;
            }

            $users = $users->get(['name', 'created_at']);

            return ['status'=> true, 'data' => $users];
            
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
}
