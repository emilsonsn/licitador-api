<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;

class SettingController extends Controller
{

    public function search(){
        try{
            $setting = Setting::orderBy('id', 'asc')->first();

            if(!isset($setting)) throw new Exception('Configuração não encontrada');
    
            return response()->json([
                'status' => true,
                'message' => "Configuração encontrada com sucesso",
                'data' => $setting,
                'error' => null
            ]); 
        }catch(Exception $error){
            return response()->json([
                'status' => false,
                'message' => null,
                'data' => null,
                'error' => $error->getMessage()
            ]);
        }
    }

    public function update(Request $request){
        try{
            $data = $request->all();
    
            if ($request->hasFile('banner')) {
                $path = $request->file('banner')->store('banners', 'public');
                $data['banner'] = $path;
            }
    
            $setting = Setting::orderBy('id', 'asc')->first();
    
            if(!isset($setting)) throw new Exception('Configuração não encontrada');
    
            $setting->update($data);
    
            return response()->json([
                'status' => true,
                'message' => "Configuração alterada com sucesso",
                'data' => $setting,
                'error' => null
            ]); 
        }catch(Exception $error){
            return response()->json([
                'status' => false,
                'message' => null,
                'data' => null,
                'error' => $error->getMessage()
            ]); 
        }
    }
}
