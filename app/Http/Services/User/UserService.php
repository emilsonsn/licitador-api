<?php

namespace App\Services\User;

use App\Models\PasswordRecovery;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{

    public function requestRecoverPassword($email){
        $user = User::where('email', $email)->first();

        if($user){
            $code = bin2hex(random_bytes(10));
            $recovery = PasswordRecovery::create([
                'code' => $code,
                'user_id' => $user->id
            ]);

            // Send email with recovery link
        }
    }

    public function updatePassword($code, $password){
        $recovery = PasswordRecovery::where('code', $code)->first();

        if($recovery){
            $user = User::find($recovery->user_id);
            $user->password = Hash::make($password);
            $user->save();

            $recovery->delete();
        }
    }
}