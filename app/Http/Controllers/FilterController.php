<?php

namespace App\Http\Controllers;

use App\Services\Filter\FilterService;
use Illuminate\Http\Request;

class FilterController extends Controller
{

    private $filterService;

    public function __construct(FilterService $filterService) {
        $this->filterService = $filterService;
    }


    public function getFilter(){
        $result = $this->filterService->getByUser();

        return $this->response($result);
    }
    
    public function createOrUpdate(Request $request){
        $result = $this->filterService->createOrUpdate($request);

        if ($result['status']) $result['message'] = "Filtro favoritado com sucesso";
        return $this->response($result);
    }

    private function response($result){
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null
        ], $result['statusCode'] ?? 200);
    }

}
