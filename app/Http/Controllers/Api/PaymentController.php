<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use App\Models\Order;
use App\Models\OrderProduct;
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
        $discount = $request->discount;
        $price = $request->amount + $request->pickup;
       
        $amount = $discount == true ? $price-(0.01*$price) : $price;

        $data = array(
        "amount" => $amount*100,
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
        $reference = $request->payment_reference;

        $curl = curl_init();
         //CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
  
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/".$reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer sk_test_288b97779ff8c9f143ab3887da7e36d2f67297dc",
            "Cache-Control: no-cache",
            ),
        ));

        
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        
      
        $data = json_decode($response);


        if ($data && $data->data->status === 'success') {

             $price = $request->total_price;
             $discount = $request->referrer_code !== null ? 0.01*$price : 0;
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
                
                return response()->json(['message' => 'Payment successful', 'data' => $order]);

        } else {
                
            return response()->json(['message' => 'Payment failed', 'data' => 'Payment not verified'], 422);
        }
    }
}
