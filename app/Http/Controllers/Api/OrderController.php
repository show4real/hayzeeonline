<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\SignUpTrait;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderProduct;

class OrderController extends Controller
{
    use SignUpTrait;

    public function index(Request $request)
    {
        $orders = Order::searchAll($request->search)
            ->latest()
            ->with('user')
            ->with('products')
            ->paginate(100);
        return response()->json(compact('orders'));
    }

    public function addOrder(Request $request)
    {


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

        return response()->json(compact('order'));
    }

    public function delete($id)
    {
        $order = Order::find($id);
        $orderProduct = OrderProduct::where('order_id', $id)->first();

        if ($order) {
            $order->delete();
            if($orderProduct){
                $orderProduct->delete();
            }
            return response()->json(true);
        }
        return response()->json(['message' => 'order not found'], 404);
    }
}
