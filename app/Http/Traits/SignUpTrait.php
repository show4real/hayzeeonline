<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

trait SignUpTrait
{
    public function addUser(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|unique:users,email',
        ]);

        $user = $this->createUser($request);

        //$token = $user->createToken('API Token')->accessToken;
        return response()->json(compact('user'), 200);
    }



    private function createUser(Request $request)
    {
        return User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'admin' => $request->admin,
            'password' => bcrypt($request->password),

        ]);
    }
}