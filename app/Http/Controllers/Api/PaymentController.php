<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Unicodeveloper\Paystack\Paystack;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private $paystack;

    public function __construct(Paystack $paystack)
    {
        $this->paystack = $paystack;
    }

    public function initiatePayment(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $amount = $request->input('amount');

        $reference = 'PAYSTACK_' . time();

        $payment = $this->paystack->getAuthorizationUrl()->redirectNow();

        return response()->json(['payment_url' => $payment->getTargetUrl(), 'reference' => $reference]);
    }

    public function handlePaymentCallback(Request $request)
    {
        $payment_details = $this->paystack->getPaymentData();
        $message = 'Payment successful';

        return response()->json(compact('message','payment_details'));
    }
}
