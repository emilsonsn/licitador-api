<?php

namespace App\Http\Controllers;

use App\Services\File\FileService;
use Illuminate\Http\Request;

class FileController extends Controller
{

    private $fileService;

    public function __construct(FileService $fileService) {
        $this->fileService = $fileService;
    }

    public function search(Request $request){
        $result = $this->fileService->search($request);

        return $this->response($result);
    }
    
    public function create(Request $request){
        $result = $this->fileService->create($request);

        if ($result['status']) $result['message'] = "Arquivo criado com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->fileService->update($request, $id);

        if ($result['status']) $result['message'] = "Arquivo atualzado com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->fileService->delete($id);

        if ($result['status']) $result['message'] = "Arquivo deletado com sucesso";
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