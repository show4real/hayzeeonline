<?php

namespace Tests\Feature;

use App\Models\HealthSupplementProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthSupplementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_list_health_supplement_products(): void
    {
        $create = $this->postJson('/api/health-supplements/products', [
            'name' => 'Vitamin C 1000mg',
            'description' => 'Immune support tablets (60 count)',
            'price' => 8500,
            'image' => 'https://example.com/images/vitamin-c.jpg',
        ]);

        $create->assertStatus(201);

        $list = $this->getJson('/api/health-supplements/products');
        $list->assertOk();
        $list->assertJsonPath('products.data.0.name', 'Vitamin C 1000mg');
    }

    public function test_can_update_health_supplement_product(): void
    {
        $p = HealthSupplementProduct::create([
            'name' => 'Zinc 50mg',
            'description' => 'Immune support tablets',
            'price' => 5000,
            'image' => 'https://example.com/images/zinc.jpg',
            'availability' => 1,
        ]);

        $res = $this->postJson('/api/health-supplements/products/' . $p->id, [
            'price' => 5500,
            'availability' => 0,
        ]);

        $res->assertOk();
        $res->assertJsonPath('product.price', 5500);
        $res->assertJsonPath('product.availability', 0);
    }

    public function test_can_create_order_for_health_supplement_products(): void
    {
        $p1 = HealthSupplementProduct::create([
            'name' => 'Omega 3 Fish Oil',
            'description' => 'Heart support 100 softgels',
            'price' => 12000,
            'image' => 'https://example.com/images/omega-3.jpg',
            'availability' => 1,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Amina Musa',
                'email' => 'amina@example.com',
                'phone' => '08000000000',
                'address' => '23 Airport Road, Abuja',
            ],
            'payment' => [
                'reference' => 'TEST_REF_123',
                'provider' => 'paystack',
                'status' => 'paid',
            ],
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 2, 'price' => 12000],
            ],
        ];

        $res = $this->postJson('/api/health-supplements/orders', $payload);
        $res->assertStatus(201);
    $res->assertJsonPath('order.customer_email', 'amina@example.com');
        $res->assertJsonPath('order.total_price', 24000);

        $orderId = $res->json('order.id');
        $get = $this->getJson('/api/health-supplements/orders/' . $orderId);
        $get->assertOk();
    $get->assertJsonPath('order.items.0.product_id', $p1->id);
    }
}
