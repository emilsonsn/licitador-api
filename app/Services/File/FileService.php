<?php

namespace App\Services\File;

use App\Models\File;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FileService
{
    
    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;
            $userId = Auth::user()->id;

            $files = File::with('category')
                ->where('user_id', $userId);

            if(isset($search_term)){
                $files->where('description', 'LIKE', "%{$search_term}%")
                    ->orWhere('filename', 'LIKE', "%{$search_term}%");
            }

            $files = $files->paginate($perPage);

            return ['status' => true, 'data' => $files];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $request['expiration_date'] = $request['expiration_date'] == 'null' ? null : $request['expiration_date'];

            $rules = [
                'description' => ['required', 'string', 'max:255'],
                'expiration_date' => ['nullable', 'date'],
                'category_id' => ['required', 'integer'],
                'file' => ['required', 'file', 'mimes:pdf,jpeg,png,doc,docx'],
            ];

            $requestData = $request->all();
            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);

            $file = $request->file('file');
            $filePath = $file->store('files', 'public');
    
            $requestData['filename'] = $file->getClientOriginalName();
            $requestData['path'] = $filePath;
    
            $fileRecord = File::create($requestData);

            return ['status' => true, 'data' => $fileRecord];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $file_id)
    {
        try {
            $request['file'] = $request['file'] == 'null' ? null : $request['file'];
            $request['expiration_date'] = $request['expiration_date'] == 'null' ? null : $request['expiration_date'];

            $rules = [
                'description' => ['required', 'string', 'max:255'],
                'expiration_date' => ['nullable', 'date'],
                'category_id' => ['required', 'integer'],
                'file' => ['nullable', 'file', 'mimes:pdf,jpeg,png,doc,docx'],
            ];

            $requestData = $request->all();
            $requestData['user_id'] = Auth::user()->id;

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $fileToUpdate = File::find($file_id);

            if(!isset($fileToUpdate)) throw new Exception('Arquivo não encontrado');

            $requestData = $validator->validated();

            if($request['file']){
                $file = $request->file('file');
                $filePath = $file->store('files', 'public');
        
                $requestData['filename'] = $file->getClientOriginalName();
                $requestData['path'] = $filePath;
            }

            $fileToUpdate->update($requestData);

            return ['status' => true, 'data' => $fileToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id){
        try{
            $file = File::find($id);

            if(!isset($file)) throw new Exception('Arquivo não encontrado');

            $fileName = $file->name;

            $file->delete();

            return ['status' => true, 'file' => $fileName];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }        
    }
}
