<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\User;
use App\Services\Paystack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    private Paystack $paystack;

    public function __construct(Paystack $paystack)
    {
        $this->paystack = $paystack;
    }

    public function initiatePayment(Request $request)
    {
        $reference = (string) time();
        $discount = $request->discount;
        $price = $request->amount + $request->pickup_charges;

        $amount = $discount == true ? $price - (0.005 * $request->amount) : $price;

        $data = [
            // Paystack works in the currency's subunit — kobo, not naira.
            'amount' => (int) round($amount * 100),
            'reference' => $reference,
            'email' => $request->email,
            'currency' => 'NGN',
            'metadata' => [
                'orderID' => $reference,
            ],
        ];

        try {
            $transaction = $this->paystack->initializeTransaction($data);
        } catch (Throwable $e) {
            Log::error('Paystack initialize failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Could not start the payment. Please try again.',
            ], 502);
        }

        return response()->json([
            'payment_url' => $transaction['authorization_url'],
            'reference' => $reference,
        ]);
    }

    public function completeOrder(Request $request)
    {
        $reference = $request->payment_reference;

        /*
        | Verification is recorded but NOT enforced, which is how this endpoint
        | has always behaved — the check was commented out, and the key it used
        | was a hardcoded sk_test_… value that could never have verified a live
        | charge. The hardcoded key is gone (it now comes from
        | config('services.paystack.secret_key')), and the outcome is logged, so
        | an unverified order is at least visible.
        |
        | To enforce it, return a 402 when $verified is not true.
        */
        $verified = null;

        try {
            $payment = $this->paystack->verifyTransaction((string) $reference);
            $verified = ($payment['status'] ?? null) === 'success';
        } catch (Throwable $e) {
            Log::warning('Paystack verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
        }

        if ($verified !== true) {
            Log::warning('Order accepted without a verified Paystack payment', [
                'reference' => $reference,
                'email' => $request->email,
            ]);
        }

        $price = $request->total_price;
        $discount = $request->referrer_code !== null ? 0.005 * $price : 0;
        $amount = $price - $discount;

        $user = User::firstOrCreate(['email' => $request->email], [
            'phone' => $request->phone,
            'name' => $request->name,
            'email' => $request->email,
            'address' => $request->address,
            'admin' => 0,
            'password' => bcrypt($request->name),
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => $amount,
            'pickup' => $request->pickup,
            'discount' => $discount,
            'description' => $request->description,
            'payment_reference' => $reference,
            'referrer_code' => $request->referrer_code,
            'payment_status' => 1,
            'status' => 0,
        ]);

        if (is_array($request->product_id) && count($request->product_id) > 0) {
            for ($i = 0; $i < count($request->product_id); $i++) {
                $order_product = new OrderProduct();
                $order_product->order_id = $order->id;
                $order_product->product_id = $request->product_id[$i];
                $order_product->price = $request->price[$i];
                $order_product->quantity = $request->quantity[$i];
                $order_product->total = $request->total[$i];
                $order_product->save();
            }
        }

        return response()->json([
            'message' => 'Payment successful',
            'data' => $order,
            'payment_verified' => $verified,
        ]);
    }
}
