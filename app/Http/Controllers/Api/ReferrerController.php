<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Referrer;
use App\Models\Transaction;
use App\Models\User;
use App\Jobs\ApprovalMail;

class ReferrerController extends Controller
{



    public function referrers(Request $request){

        $referrers = Referrer::with('user')
        ->search($request->search)
        ->latest()
       ->paginate($request->rows, ['*'], 'page', $request->page);

        return response()->json(compact('referrers'));

    }



     public function allReferrers(Request $request){

        $referrers = Referrer::with('user')->get();

        return response()->json(compact('referrers'));

    }


    public function referrerCode(Request $request){
        $referrer_codes = Referrer::pluck('referral_code');

        return response()->json(compact('referrer_codes'));
        
    }

    //    public function deleteReferrer($id)
    // {
    //     $referrer = Referrer::find($id);
       

    //     if ($referrer) {
    //         $user = User::where('id', $referrer->user_id)->first();

    //         $transactions = Transaction::where('referrer_id', $id)->get();
           
           
    //         $referrer->delete();
    //         $user->delete();
    //         $transactions->delete();

    //         return response()->json(true);
    //     }
    //     return response()->json(['message' => 'referrer not found'], 404);
    // }
    public function deleteReferrer($id){

        $referrer = Referrer::find($id);

        if ($referrer) {
            
            $referrer->user->delete();
            $referrer->transactions()->delete();

           
            $referrer->delete();

            return response()->json(true);
        }

        return response()->json(['message' => 'referrer not found'], 404);
    }

    public function referrerProfile(Request $request){
        $referrer = Referrer::where('user_id', auth()->user()->id)->first();

        
         return response()->json(compact('referrer'));

    }

    public function addProfile(Request $request){


        $authuser = auth()->user();

        $referrer = Referrer::where('user_id', $authuser->id)->first();
        $user = User::find($authuser->id);
        $user->phone = $request->phone;
        $user->save();

        

        $referrer->address= $request->address;
        $referrer->bank_name = $request->bank_name;
        $referrer->account_name = $request->account_name;
        $referrer->account_type = $request->account_type;
        $referrer->account_number = $request->account_number;
        $referrer->save();

        

        return response()->json(compact('referrer'));

    }


    public function approve(Request $request){

        $referrer= Referrer::where('id', $request->referrer_id)->first();
        if($referrer){
            $referrer->status = $request->status;
            $referrer->approved_at = Carbon::now();
            $referrer->approver = auth()->user()->id;

            $referrer->save();

            ApprovalMail::dispatch($referrer);
            

            return response()->json(compact('referrer'));
        }
        return response()->json(compact('referrer'),404);
    }


    public function addTransaction(Request $request){

        $referrer = Referrer::where('referral_code', $request->referrer_code)->first();

        $product_cost = $request->product_cost;
        $percentage = $request->percentage;

        $amount_paid =  ($percentage / 100 * $product_cost);

        $transaction = new Transaction();
        $transaction->product_cost = $product_cost;
        $transaction->percentage = $percentage;
        $transaction->paid = $amount_paid;
        $transaction->user_id = $referrer->user_id;
        $transaction->referrer_id = $referrer->id;
        $transaction->status = $request->status;
        $transaction->approved_by = auth()->user()->id;
        $transaction->save();

        return response()->json(compact('transaction'));

    }


     public function updateTransaction(Request $request, $id){

        $referrer = Referrer::where('referral_code', $request->referrer_code)->first();

        $product_cost = $request->product_cost;
        $percentage = $request->percentage;

        $amount_paid =  ($percentage / 100 * $product_cost);

        $transaction = Transaction::where('id', $id)->first();
        $transaction->product_cost = $product_cost;
        $transaction->percentage = $percentage;
        $transaction->paid = $amount_paid;
        $transaction->user_id = $referrer->user_id;
        $transaction->referrer_id = $referrer->id;
        $transaction->status = $request->status;
        $transaction->approved_by = auth()->user()->id;
        $transaction->save();

        return response()->json(compact('transaction'));

    }

    public function allTransactions(Request $request){

        $transactions = Transaction::search($request->search)
            ->referrer($request->referrer_id)
            ->with('referrer')
           ->paginate($request->rows, ['*'], 'page', $request->page);
    
        return response()->json(compact('transactions'));
    }

    public function myTransactions(Request $request){

        $transactions = Transaction::where('user_id', auth()->user()->id)
        ->filter1($request->get('fromdate'))
        ->filter2($request->get('todate'))
        ->paginate($request->rows, ['*'], 'page', $request->page);
        
        return response()->json(compact('transactions'));
    }

      public function deleteTransaction($id)
    {
        $transaction = Transaction::find($id);

        if ($transaction) {
           
            $transaction->delete();
            return response()->json(true);
        }
        return response()->json(['message' => 'transaction not found'], 404);
    }


    public function referrerDashboard(Request $request){

        $referrer = Referrer::where('user_id', auth()->user()->id)->first();

        $status = $referrer->status == 1 ? 'Active' : 'Pending';

        $earnings=Transaction::
        where('user_id', auth()->user()->id)
        ->where('status', 1)
        ->get();
        $total_earnings=0;
        $total_transactions=count($earnings);
        foreach($earnings as $earning){
            
            $earnings=$earning['paid'];
             $total_earnings+=$earnings;
             
        }

         return response()->json(compact('total_transactions', 'total_earnings','status'));
    }



}
