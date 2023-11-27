<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Paystack;

class PaymentController extends Controller
{
    private $paystack;

    public function __construct(Paystack $paystack)
    {
        $this->paystack = $paystack;
    }

    public function initiatePayment(Request $request)
    {
        $reference = time();

        $data = array(
        "amount" => $request->amount,
        "reference" => $reference,
        "email" => $request->email,
        "currency" => "NGN",
        "orderID" => time(),
        );

        $payment = Paystack::getAuthorizationUrl($data)->redirectNow();
       

        return response()->json(['payment_url' => $payment->getTargetUrl(), 'reference' => $reference]);
    }

    public function handlePaymentCallback(Request $request)
    {
        $payment_details = $this->paystack->getPaymentData();
       
        if($payment_details){
             $message = 'Payment successful';
             return response()->json(compact('message','payment_details'),200);
        }
             $message = 'Access denied';
             return response()->json(compact('message'),403);

       
    }
}
