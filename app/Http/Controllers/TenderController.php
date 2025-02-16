<?php

namespace App\Http\Controllers;

use App\Services\Tender\TenderService;
use App\Traits\PncpTrait;
use Illuminate\Http\Request;

class TenderController extends Controller
{
    private $tenderService;

    public function __construct(TenderService $tenderService) {
        $this->tenderService = $tenderService;
    }

    public function search(Request $request){
        $result = $this->tenderService->search($request);
        return $this->response($result);
    }

    public function delete($tender_id){
        $result = $this->tenderService->delete($tender_id);
        return $this->response($result);
    }

    public function edital($idLicitacao){
        $result = $this->tenderService->edital($idLicitacao);
        return $this->response($result);
    }

    public function note(Request $request){
        $result = $this->tenderService->noteStore($request);

        if($result['status']) $result['message'] = 'Anotação criada com sucesso';
        return $this->response($result);
    }

    public function noteDelete($id){
        $result = $this->tenderService->noteDelete($id);

        if($result['status']) $result['message'] = 'Anotação deletada com sucesso';
        return $this->response($result);
    }

    private function response($result){

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null
        ]);
    }
}
