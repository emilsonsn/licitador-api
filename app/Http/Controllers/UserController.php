<?php

namespace App\Http\Controllers;

use App\Services\User\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function search(Request $request){
        $response = $this->userService->search($request);

        if($response['status']){
            return response()->json([
                'status' => true,
                'data' => $response['data'],
                'message' => '',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $response['error'],
            ]);
        }
    }

    public function userBlock($user_id){
        $response = $this->userService->userBlock($user_id);

        if($response['status']){
            return response()->json([
                'status' => true,
                'data' => $response['data'],
                'message' => 'Bloqueio do usuário alterado com sucesso',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $response['error'],
            ]);
        }
    }

    public function passwordRecovery(Request $request){
        $response = $this->userService->requestRecoverPassword($request);

        if($response['status']){
            return response()->json([
                'status' => true,
                'data' => $response['data'],
                'message' => 'Código de recuperação enviado com sucesso',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $response['error'],
            ]);
        }
    }

    public function updatePassword(Request $request){
        $response = $this->userService->updatePassword($request);

        if($response['status']){
            return response()->json([
                'status' => true,
                'data' => $response['data'],
                'message' => 'Senha recuperada com sucesso',
            ]);
        }else{
            return response()->json([
                'status' => false,
                'data' => null,
                'error' => $response['error'],
            ]);
        }
    }
}
