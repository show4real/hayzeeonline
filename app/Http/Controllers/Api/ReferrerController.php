<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Referrer;
use App\Models\Transaction;

class ReferrerController extends Controller
{



    public function referrals(Request $request){

        $referrers = Referrer::with('users')->paginate(10);

        return response()->json(compact('referrers'));

    }

    public function addProfile(Request $request){


        $user = auth()->user();

        $referrer = Referrer::where('user_id', $user->id)->first();

        $referrer->address= $request->address;
        $referrer->bank_name = $request->bank_name;
        $referrer->account_name = $request->account_name;
        $referrer->account_type = $request->account_type;
        $referrer->account_number = $request->account_number;
        $referrer->save();

        return response()->json(compact('referrer'));

    }


    public function approve(Request $request){

        $referrer= Referrer::where('user_id', $request->referrer_id)->first();
        if($referrer){
            $referrer->status = $request->status;
            $referrer->approved_at = Carbon::now();
            $referrer->approver = auth()->user()->id;

            $referrer->save();

            return response()->json(compact('referrer'));
        }
    }


    public function addTransaction(Request $request){

        $referrer = Referrer::where('referrer_code', $request->referrer_code)->first();

        $product_cost = $request->product_cost;

        $amount_paid = $product_cost - (($request->percentage/100)*$product_cost);

        $transaction = new Transaction();
        $transaction->product_cost = $product_cost;
        $transaction->amount_paid = $amount_paid;
        $transaction->user_id = $referrer->user_id;
        $transaction->referrer_id = $referrer->referrer_id;
        $transaction->status = $request->status;
        $transaction->save();

        return response()->json(compact('transaction'));

    }

    public function allTransactions(Request $request){

        $transactions = Transaction::where('referrer_id', $request->referrer_id)->paginate(10);
        
        return response()->json(compact('transactions'));
    }

    public function myTransactions(Request $request){

        $transactions = Transaction::where('user_id', auth()->user()->id)->paginate(10);
        
        return response()->json(compact('transactions'));
    }



}
