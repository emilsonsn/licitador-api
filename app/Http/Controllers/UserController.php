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
        $result = $this->userService->search($request);

        return $this->response($result);
    }

    public function getUser(){
        $result = $this->userService->getUser();

        return $this->response($result);
    }
    
    public function create(Request $request){
        $result = $this->userService->create($request);

        $result['message'] = "Usuário criado com sucesso";
        return $this->response($result);
    }

    public function update(Request $request, $id){
        $result = $this->userService->update($request, $id);
        
        $result['message'] = "Usuário atualizado com sucesso";
        return $this->response($result);
    }

    public function userBlock($id){
        $result = $this->userService->userBlock($id);

        $result['message'] = "Ação realizada com sucesso";
        return $this->response($result);
    }

    public function passwordRecovery(Request $request){
        $result = $this->userService->requestRecoverPassword($request);

        $result['message'] = "Email de recuperação enviado com sucesso";
        return $this->response($result);
    }

    public function updatePassword(Request $request){
        $result = $this->userService->updatePassword($request);
        $result['message'] = "Senha atualizada com sucesso";
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
