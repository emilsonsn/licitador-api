<?php

namespace App\Services\User;

use App\Models\PasswordRecovery;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordRecoveryMail;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserService
{

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;

            $users = User::where('is_admin', 0);

            if(isset($search_term)){
                $users->where('name', 'LIKE', "%{$search_term}%")
                    ->orWhere('email', 'LIKE', "%{$search_term}%")
                    ->orWhere('phone', 'LIKE', "%{$search_term}%");
            }

            $users = $users->paginate($perPage);

            return ['status' => true, 'data' => $users];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function getUser()
    {
        try {
            $user = auth()->user();

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }


    public function create($request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $password = Str::random(10);
    
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'is_admin' => false,
                'is_active' => true,
            ]);

            Mail::to($user->email)->send(new WelcomeMail($user->name, $user->email, $password));

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $rules = [
                'name' => 'nullable|string|max:255',
                'surname' => 'nullable|string|max:255',
                "phone" => 'nullable|string|max:255',
                'birthday' => 'nullable|date',
                'postalcode' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'cnpj' => 'nullable|string|max:255',
                'corporate_reason' => 'nullable|string|max:255',
                'fantasy_name' => 'nullable|string|max:255',
                'opening_date' => 'nullable|date',
            ];


            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $userToUpdate = User::find($user_id);

            if(!isset($userToUpdate)) throw new Exception('usuário não encontrado');

            $userToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $userToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function userBlock($user_id)
    {
        try {
            $user = User::find($user_id);

            if (!$user) throw new Exception('Usuário não encontrado');

            $user->is_active = !$user->is_active;
            $user->save();

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function requestRecoverPassword($request)
    {
        try {
            $email = $request->email;
            $user = User::where('email', $email)->first();
                
            if (!isset($user)) throw new Exception('Usuário não encontrado.');
            
            $code = bin2hex(random_bytes(10));

            $recovery = PasswordRecovery::create([
                'code' => $code,
                'user_id' => $user->id
            ]);

            if (!$recovery) {
                throw new Exception('Erro ao tentar recuperar senha');
            }

            Mail::to($email)->send(new PasswordRecoveryMail($code));
            return ['status' => true, 'data' => $user];

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
