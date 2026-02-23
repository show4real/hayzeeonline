<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthSupplementOrder;
use App\Models\HealthSupplementOrderItem;
use App\Models\HealthSupplementProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class HealthSupplementController extends Controller
{
    /**
     * Contract
    * - Uses dedicated models + tables for health supplements.
    * - Products: health_supplement_products
    * - Orders: health_supplement_orders
    * - Items: health_supplement_order_items
     */

    public function createProduct(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'availability' => ['nullable', 'integer'],
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $dir = public_path('health_supplements');
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $file = $request->file('image');
            $imageName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $imageName);
            $imageUrl = URL::asset('health_supplements/' . $imageName);
        }

        $data['image'] = $imageUrl;

        $product = HealthSupplementProduct::create($data);

        return response()->json([
            'message' => 'Product created',
            'product' => $product,
        ], 201);
    }

    public function retrieveProducts(Request $request)
    {
        $rows = (int) $request->input('rows', 15);
        $rows = $rows > 0 ? min($rows, 100) : 15;

        $search = trim((string) $request->input('search', ''));

    $query = HealthSupplementProduct::query()->orderBy('updated_at', 'desc');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('description', 'like', $like);
            });
        }

    $products = $query->paginate($rows);

        return response()->json(compact('products'));
    }

    public function updateProduct(Request $request, $id)
    {
        $product = HealthSupplementProduct::query()->find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'image' => ['sometimes', 'nullable', 'file', 'image', 'max:5120'],
            'availability' => ['sometimes', 'integer'],
        ]);

        if ($request->hasFile('image')) {
            // Delete previous image file if it was stored locally
            if (is_string($product->image) && $product->image !== '') {
                $path = parse_url($product->image, PHP_URL_PATH);
                if (is_string($path) && str_starts_with($path, '/health_supplements/')) {
                    $filePath = public_path(ltrim($path, '/'));
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }
            }

            $dir = public_path('health_supplements');
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $file = $request->file('image');
            $imageName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $imageName);
            $data['image'] = URL::asset('health_supplements/' . $imageName);
        }

        $product->fill($data);
        $product->save();

        return response()->json([
            'message' => 'Product updated',
            'product' => $product,
        ]);
    }

    /**
     * Payload format (example):
     * {
     *   "customer": {"name":"Amina Musa","email":"amina@example.com","phone":"080...","address":"..."},
     *   "payment": {"reference":"PSK_123","provider":"paystack","status":"paid"},
     *   "items": [{"product_id": 1, "quantity": 2, "price": 15000}]
     * }
     */
    public function requestProductsAsOrder(Request $request)
    {
        $data = $request->validate([
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:50'],
            'customer.address' => ['nullable', 'string', 'max:1000'],

            'payment.reference' => ['nullable', 'string', 'max:120'],
            'payment.provider' => ['nullable', 'string', 'max:60'],
            'payment.status' => ['nullable', 'string', 'max:60'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:health_supplement_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($data) {
            $customer = $data['customer'];
            $payment = $data['payment'] ?? [];

            $total = collect($data['items'])->sum(function ($i) {
                return ((int) $i['quantity']) * ((float) $i['price']);
            });

            $order = HealthSupplementOrder::create([
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'customer_address' => $customer['address'] ?? null,
                'total_price' => (int) round($total),
                'status' => 0,
                'payment_reference' => $payment['reference'] ?? null,
                'payment_provider' => $payment['provider'] ?? null,
                'payment_status' => $payment['status'] ?? null,
                'notes' => isset($data['notes']) ? (string) $data['notes'] : null,
            ]);

            foreach ($data['items'] as $item) {
                HealthSupplementOrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'price' => (int) round($item['price']),
                    'quantity' => (int) $item['quantity'],
                    'total' => (int) round($item['price'] * $item['quantity']),
                ]);
            }

            $order->load(['items.product']);

            return response()->json([
                'message' => 'Order created',
                'order' => $order,
            ], 201);
        });
    }

    public function retrieveOrderInfo(Request $request, $id)
    {
    $order = HealthSupplementOrder::query()->with(['items.product'])->find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'order' => $order,
        ]);
    }
}
