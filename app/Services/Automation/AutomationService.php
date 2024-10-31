<?php

namespace App\Services\Automation;

use Exception;
use App\Models\Automation;
use Illuminate\Support\Facades\Validator;

class AutomationService
{

    public function search()
    {
        try {
            $automations = Automation::orderBy('id', 'desc')->paginate(10);

            return ['status' => true, 'data' => $automations];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function create($request)
    {
        try {
            $request['modality_ids'] = is_array($request->modality_ids) ? $request->modality_ids : explode(',',$request->modality_ids);

            $rules = [
                'state' => ['required', 'string', 'max:2'],
                'city' => ['required', 'string'],
            ];
    
            $validator = Validator::make($request->all(), $rules);
    
            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }
    
            $data = $validator->validate();
    
            $automation = Automation::create($data);

            return ['status' => true, 'data' => $automation];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
}