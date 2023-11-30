<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\User;
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

    public function completeOrder(Request $request)
    {
        $paymentReference = $request->payment_reference;
        $data = array(
        "reference" => $request->payment_reference,
        "email" => $request->email,
        );


        $paymentDetails = Paystack::getPaymentData($data);


        if ($paymentDetails['data']['status'] === 'success') {

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
                'total_price' => $request->total_price,
                'description' => $request->description,
                'payment_reference' => $request->payment_reference,
                'payment_status' => 1,
                'status' => 0,
            ]);


            if (count($request->product_id) > 0) {
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
                
                return response()->json(['message' => 'Payment successful', 'data' => $paymentDetails]);

        } else {
                
            return response()->json(['message' => 'Payment failed', 'data' => $paymentDetails], 422);
        }
    }
}
