<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Referrer;
use Illuminate\Support\Str;
 
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
        $user = User::create([
            'phone' => $request->phone,
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'admin' => $request->admin,
            'password' => bcrypt($request->password),

        ]);

        if($request->referral){
            $username = explode('@', $user->email);
            
            $referral = new Referrer();
            $referral->referral_code = Str::random(5).$username[0];
            $referral->user_id = $user->id;
            $referral->name = $request->name;
            $referral->save();

             Mail::send(
            'mail.cart',
            [
                'referral' => $referral,
               
            ],
            function ($mail) use ($name, $subject) {
                $mail->from('test@hayzeeonline.com', 'Hayzee Computer Resources');
                $mail->to('hayzeecomputerresources@gmail.com', $name);
                $mail->subject($subject);
            }
        );
        }

        return $user;
        
    }


    

}