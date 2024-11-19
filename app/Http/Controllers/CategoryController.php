<?php

namespace App\Http\Controllers;

use App\Services\Category\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    private $categoryService;

    public function __construct(CategoryService $categoryService) {
        $this->categoryService = $categoryService;
    }

    public function all(){
        $result = $this->categoryService->all();

        return $this->response($result);
    }

    public function search(Request $request){
        $result = $this->categoryService->search($request);

        return $this->response($result);
    }
    
    public function create(Request $request){
        $result = $this->categoryService->create($request);

        if ($result['status']) $result['message'] = "Categoria criada com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->categoryService->update($request, $id);

        if ($result['status']) $result['message'] = "Categoria atualzada com sucesso";
        return $this->response($result);
    }

    public function delete($id){
        $result = $this->categoryService->delete($id);

        if ($result['status']) $result['message'] = "Categoria deletada com sucesso";
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