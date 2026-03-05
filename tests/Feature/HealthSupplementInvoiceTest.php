<?php

namespace Tests\Feature;

use App\Models\HealthSupplementProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthSupplementInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_payment_invoice_validates_payload(): void
    {
        $p1 = HealthSupplementProduct::create([
            'name' => 'Omega 3 Fish Oil',
            'description' => 'Heart support 100 softgels',
            'price' => 12000,
            'image' => 'https://example.com/images/omega-3.jpg',
            'availability' => 1,
        ]);

        // Missing customer.email should fail validation
        $res = $this->postJson('/api/health-supplements/create-payment-invoice', [
            'customer' => ['name' => 'Amina Musa'],
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 1, 'price' => 12000],
            ],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['customer.email']);
    }
}
