<?php

namespace App\Services\Webhook;

use App\Mail\WelcomeMail;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class WebhookService
{

    public function handle($request)
    {
        try {
            $event = $request->input('event');

            if(!isset($event)) throw new Exception('Invalid event');

            switch($event){
                case "PURCHASE_APPROVED":
                    $result = $this->createUser($request);
                    break;
                case "SUBSCRIPTION_CANCELLATION":
                case "PURCHASE_DELAYED":
                case "PURCHASE_REFUNDED":
                    $result = $this->blockUser($request);
                    break;                
            }
            
            return ['status' => true, 'data' => $result];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function createUser($request)
    {
        try {
            $buyer = $request['data']['buyer'];

            $user = User::where('email', $buyer['email'])->first();

            if(isset($user)){
                $user->update([
                    'is_active' => true
                ]);        

            }else{
                $password = Str::random(10);
    
                $user = User::create([
                    'name' => $buyer['name'],
                    'email' => $buyer['email'],
                    'password' => Hash::make($password),
                    'is_admin' => false,
                    'is_active' => true,
                ]);

                Mail::to($user->email)->send(new WelcomeMail($user->name, $user->email, $password));
            }

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function blockUser($request)
    {
        try {
            $email = $request['data']['buyer']['email'];

            $user = User::where('email', $email)
                ->first();

            $user->update([
                'is_active' => false,
            ]);

            return ['status' => true, 'data' => $user];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }
}
