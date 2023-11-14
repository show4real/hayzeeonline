<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Referrer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Jobs\VerifyMail;
 
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

        if($request->referrer){
            $username = explode('@', $user->email);
            
            $referrer = new Referrer();
            $referrer->referral_code =  substr_replace(Str::random(5), $user->id, 2, 0);
            $referrer->user_id = $user->id;
            $referrer->name = $request->name;
            $referrer->email = $request->email;
            $referrer->save();

             $name = $request->name;
                $email = $request->email;
                $subject = 'Hayzee Computer Resources Referral Registration';

                VerifyMail::dispatch($referrer);
                 

        //      Mail::send(
        //     'mail.verify',
        //     [
        //         'referrer' => $referrer,
        //         'email' => $email
               
        //     ],
        //     function ($mail) use ($name, $email, $subject) {
        //         $mail->from('test@hayzeeonline.com', 'Hayzee Computer Resources');
        //         $mail->to($email, $name);
        //         $mail->subject($subject);
        //     }
        // );

        //return $referrer;
        
        }

         return $user;

       
        
    }


    

}