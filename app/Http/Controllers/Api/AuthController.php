<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\SignUpTrait;
use App\Models\Referrer;
use App\Models\User;
use App\Jobs\ApprovalMail;


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

    public function loginReferrer(Request $request)
    {

        $credentials = $request->only('email', 'password');


        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user = auth()->user();
        if($user->email_verified_at == null){
             return response()->json(['error' => 'Unauthorized'], 401);
        }
       
        return response()->json([
            'user' => $user,
            'token' => $token,

        ]);
    }



    public function verify(Request $request){
      $referrer = Referrer::where('referral_code', $request->referrer_code)->first();


      $user = $referrer !== null ? User::where('id', $referrer->user_id)->first() : null;

      if($user && $user->verified == null){
        $referrer->verified = 1;
        $referrer->save();
        
        $user->email_verified_at = now();
        $user->save();

        ApprovalMail::dispatch($referrer);

        return response()->json(compact('user'));
        
        } else {
             $user= 'expired';
            return response()->json(compact('user'),401);

        }
     
     
    }
}