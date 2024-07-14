<?php

namespace App\Services\User;

use App\Models\PasswordRecovery;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryMail;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function requestRecoverPassword($request)
    {
        try {
            $email = $request->email;
            $user = User::where('email', $email)->first();

            if ($user) {
                $code = bin2hex(random_bytes(10));
                $recovery = PasswordRecovery::create([
                    'code' => $code,
                    'user_id' => $user->id
                ]);

                if (!$recovery) {
                    throw new Exception('Erro ao tentar recuperar senha');
                }

                $mail = Mail::to('emilsonsn2@gmail.com')->send(new PasswordRecoveryMail($code));

                return ['status' => true, 'data' => $user];
            } else {
                throw new Exception('Usuário não encontrado.');
            }

            return ['status' => true];
        } catch (Exception $error) {
            Log::error('Erro na recuperação de senha: ' . $error->getMessage());
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }


    public function updatePassword($request){
        try{
            $code = $request->code;
            $password = $request->password;
            
            $recovery = PasswordRecovery::orderBy('id', 'desc')->where('code', $code)->first();
    
            if(!$recovery) throw new Exception('Código enviado não é válido.');
    
            $user = User::find($recovery->user_id);
            $user->password = Hash::make($password);
            $user->save();
            $recovery->delete();

            return ['status' => true, 'data' => $user];
        }catch(Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
}
