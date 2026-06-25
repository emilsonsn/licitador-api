<?php

namespace App\Http\Controllers;

use App\Services\Company\CompanyService;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function getCompany()
    {
        $result = $this->companyService->getByUser();

        return $this->response($result);
    }

    public function createOrUpdate(Request $request)
    {
        $result = $this->companyService->createOrUpdate($request);

        if ($result['status']) {
            $result['message'] = 'Empresa salva com sucesso';
        }

        return $this->response($result);
    }

    private function response($result)
    {
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ], $result['statusCode'] ?? 200);
    }
}
