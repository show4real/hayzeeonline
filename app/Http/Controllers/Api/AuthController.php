<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\SignUpTrait;
use App\Models\Referrer;
use App\Models\User;


class AuthController extends Controller
{
    use SignUpTrait;

    public function signup(Request $request)
    {

        $user = $this->addUser($request);
        return $user;
    }

    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');


        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth()->user();
        return response()->json([
            'user' => $user,
            'token' => $token,

        ]);
    }


    public function verify(Request $request){
      $referrer = Referrer::where('referral_code', $request->referrer_code)->first();

      $user = $referrer !== null ? User::where('id', $referrer->user_id)->first() : null;

      if($user && $user->status == null){

        $user->status = 1;
        $user->email_verified_at = now();
        $user->save();

        return response()->json(compact('user'));
        
        } else {
             $user= 'expired';
            return response()->json(compact('user'),401);

        }
     
     
    }
}