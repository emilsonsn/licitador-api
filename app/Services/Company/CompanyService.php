<?php

namespace App\Services\Company;

use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompanyService
{
    public function getByUser()
    {
        try {
            $company = Company::where('user_id', Auth::user()->id)->first();

            if (! isset($company)) {
                return [
                    'status' => false,
                    'error' => 'Você não tem nenhuma empresa cadastrada',
                    'statusCode' => 404,
                ];
            }

            return ['status' => true, 'data' => $company];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function createOrUpdate($request)
    {
        try {
            $rules = [
                'cnpj' => ['nullable', 'string', 'max:18'],
                'corporate_reason' => ['nullable', 'string', 'max:255'],
                'fantasy_name' => ['nullable', 'string', 'max:255'],
                'street' => ['nullable', 'string', 'max:255'],
                'number' => ['nullable', 'string', 'max:20'],
                'complement' => ['nullable', 'string', 'max:255'],
                'neighborhood' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'state' => ['nullable', 'string', 'max:255'],
                'zipcode' => ['nullable', 'string', 'max:10'],
                'phone' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'legal_representative_name' => ['nullable', 'string', 'max:255'],
                'legal_representative_rg' => ['nullable', 'string', 'max:255'],
                'legal_representative_cpf' => ['nullable', 'string', 'max:14'],
                'bank' => ['nullable', 'string', 'max:255'],
                'agency' => ['nullable', 'string', 'max:255'],
                'checking_account' => ['nullable', 'string', 'max:255'],
                'logo' => ['nullable', 'string', 'max:255'],
            ];

            if ($request->hasFile('logo')) {
                $rules['logo'] = ['nullable', 'file', 'mimes:jpg,jpeg,png', 'dimensions:width=120,height=120'];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $requestData = $validator->validated();

            if ($request->hasFile('logo')) {
                $requestData['logo'] = $request->file('logo')->store('companies/logos', 'public');
            }

            $company = Company::updateOrCreate(
                ['user_id' => Auth::user()->id],
                $requestData
            );

            return ['status' => true, 'data' => $company];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }
}
