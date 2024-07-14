<?php

namespace App\Services\Dashboard;

use App\Models\PasswordRecovery;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryMail;
use Illuminate\Support\Facades\Log;

class DashboardService
{

    public function search($request)
    {
        try {
            $userMonth = User::orderBy('created_at', 'ASC')
                ->where('is_admin', false)
                ->whereRaw('month(created_at) = month(now())')
                ->get();
            $userDay = User::where('is_admin', false)->whereRaw('CAST(created_at as DATE) = CAST(now() as DATE)')->get();
            $totalSearchMonth = 5;

            $data = [
                'usersMonth' => $userMonth,
                'totalSearchMonth' => $totalSearchMonth,
                'totalUserMonth' => $userMonth->count(),
                'totalUsersToday' => $userDay->count(),
            ];

            return ['status' => true, 'data' => $data];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
    
}
