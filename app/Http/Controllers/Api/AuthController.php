<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\SignUpTrait;


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
}