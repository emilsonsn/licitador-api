<?php

namespace App\Services\Category;

use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CategoryService
{

    public function all()
    {
        try {
            $categories = Category::get();            

            return ['status' => true, 'data' => $categories];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
    
    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;

            $categories = Category::query();

            if(isset($search_term)){
                $categories->where('description', 'LIKE', "%{$search_term}%");
            }

            $categories = $categories->paginate($perPage);

            return ['status' => true, 'data' => $categories];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function create($request)
    {
        try {
            $rules = [
                'description' => ['required', 'string', 'max:255'],
            ];

            $requestData = $request->all();
            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);
    
            $category = Category::create($requestData);

            return ['status' => true, 'data' => $category];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function update($request, $category_id)
    {
        try {
            $rules = [
                'description' => ['required', 'string', 'max:255'],
            ];

            $requestData = $request->all();
            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $categoryToUpdate = Category::find($category_id);

            if(!isset($categoryToUpdate)) throw new Exception('Categoria não encontrada');

            $categoryToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $categoryToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function delete($id){
        try{
            $category = Category::find($id);

            if(!isset($category)) throw new Exception('Categoria não encontrada');

            $categoryDescription = $category->description;

            $category->delete();

            return ['status' => true, 'category' => $categoryDescription];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }        
    }
}
