<?php

namespace App\Services\Filter;

use App\Models\PasswordRecovery;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryMail;
use App\Mail\WelcomeMail;
use App\Models\Filter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FilterService
{

    public function getByUser()
    {
        try {
            $auth = Auth::user();
            $filter = Filter::where('user_id', $auth->id)->first();

            if(!isset($filter)){
                return [
                    'status' => false,
                    'error' => "Você não tem nenhum filtro favoritado"
                ];
            }

            $filter->modality_ids = json_decode($filter->modality_ids);

            return ['status' => true, 'data' => $filter];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function createOrUpdate($request)
    {
        try {
            $request['modality_ids'] = is_array($request->modality_ids) ? $request->modality_ids : explode(',',$request->modality_ids);

            $rules = [
                'object' => 'nullable|string',
                'uf' => 'nullable|string|max:2',
                'city' => 'nullable|string',
                'modality_ids' => 'nullable|array',
                'update_date_start' => 'nullable|date',
                'update_date_end' => 'nullable|date|after_or_equal:update_date_start',
                'organ_cnpj' => 'nullable|string|max:18',
                'organ_name' => 'nullable|string|max:255',
                'process' => 'nullable|string|max:255',
                'observations' => 'nullable|string',
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
    
            $user = auth()->user();
    
            $filter = Filter::updateOrCreate([
                'user_id' => $user->id,
            ],
            [
                'object' => $request->object,
                'uf' => $request->uf,
                'city' => $request->city,
                'modality_ids' => is_array($request->modality_ids) ? json_encode($request->modality_ids) : null,
                'update_date_start' => $request->update_date_start,
                'update_date_end' => $request->update_date_end,
                'organ_cnpj' => $request->organ_cnpj,
                'organ_name' => $request->organ_name,
                'process' => $request->process,
                'observations' => $request->observations,
            ]);

            return ['status' => true, 'data' => $filter];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
}