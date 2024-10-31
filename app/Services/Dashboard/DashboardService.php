<?php

namespace App\Services\Dashboard;

use App\Models\Tender;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function search(): array
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
            Log::error('Search failed: ' . $error->getMessage());
            return ['status' => false, 'error' => 'An error occurred while retrieving data.'];
        }
    }

    public function userGraph($request): array
    {
        try {
            $users = User::where('is_admin', false);
            $period = $request->input('period', 'monthly');

            if ($period === 'monthly') {
                $users->whereMonth('created_at', now()->month);
            }

            $users = $users->get(['name', 'created_at']);

            return ['status' => true, 'data' => $users];
        } catch (Exception $error) {
            Log::error('User graph retrieval failed: ' . $error->getMessage());
            return ['status' => false, 'error' => 'An error occurred while retrieving user data.'];
        }
    }
}
