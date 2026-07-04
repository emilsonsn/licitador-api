<?php

namespace App\Services\Dashboard;

use App\Models\Tender;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
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

    public function indicators(): array
    {
        try {
            $tenders = Tender::query();
            $todayTenders = Tender::query()
                ->whereDate('created_at', now()->toDateString());

            $totalTenders = (clone $tenders)->count();
            $totalDayTenders = Tender::query()
                ->whereDate('created_at', now()->toDateString())
                ->count();
            $totalMonthTenders = Tender::query()
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
            $tendersWithValue = (clone $tenders)
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->count();
            $confidentialTenders = (clone $tenders)
                ->where(function (Builder $query) {
                    $query->whereNull('value')
                        ->orWhere('value', '<=', 0);
                })
                ->count();
            $totalTenderValue = (float) (clone $tenders)
                ->whereNotNull('value')
                ->where('value', '>', 0)
                ->sum('value');
            $averageTenderValue = $tendersWithValue > 0 ? $totalTenderValue / $tendersWithValue : 0;

            $data = [
                'users' => $this->userSummary(),
                'tenders' => [
                    'totalTenders' => $totalTenders,
                    'totalDayTenders' => $totalDayTenders,
                    'totalMonthTenders' => $totalMonthTenders,
                    'tendersWithValue' => $tendersWithValue,
                    'confidentialTenders' => $confidentialTenders,
                    'totalTenderValue' => $totalTenderValue,
                    'averageTenderValue' => $averageTenderValue,
                ],
                'distributions' => [
                    'sources' => $this->distribution($todayTenders, 'api_origin', 'Não informado'),
                    'ufs' => $this->distribution($todayTenders, 'uf', 'Não informado'),
                    'cities' => $this->distribution($tenders, 'city', 'Não informado'),
                    'modalities' => $this->distribution($tenders, 'modality', 'Não informado'),
                    'statuses' => $this->distribution($tenders, 'status', 'Não informado'),
                ],
            ];

            return ['status' => true, 'data' => $data];
        } catch (Exception $error) {
            Log::error('Dashboard indicators retrieval failed: ' . $error->getMessage());
            return ['status' => false, 'error' => 'An error occurred while retrieving dashboard indicators.'];
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

            if($period === 'annual'){
                $users->whereYear('created_at', now()->year);
            }

            $users = $users->get(['name', 'created_at']);

            return ['status' => true, 'data' => $users];
        } catch (Exception $error) {
            Log::error('User graph retrieval failed: ' . $error->getMessage());
            return ['status' => false, 'error' => 'An error occurred while retrieving user data.'];
        }
    }

    private function userSummary(): array
    {
        return [
            'totalUsers' => User::where('is_admin', false)->count(),
            'totalMonthUsers' => User::where('is_admin', false)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'totalActiveUsers' => User::where('is_admin', false)
                ->where('is_active', true)
                ->count(),
            'totalInactiveUsers' => User::where('is_admin', false)
                ->where('is_active', false)
                ->count(),
        ];
    }

    private function distribution(Builder $query, string $field, string $fallback): array
    {
        return (clone $query)
            ->selectRaw("COALESCE(NULLIF({$field}, ''), ?) as label, COUNT(*) as total", [$fallback])
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'label' => $item->label,
                'value' => (int) $item->total,
            ])
            ->values()
            ->toArray();
    }

}
