<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientConsentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_a_client_consent_via_delete_endpoint()
    {
        $id = \DB::table('client_consents')->insertGetId([
            'consent_client_full_name' => 'Jane Roe',
            'consent_client_phone' => '555-1234',
            'consent_client_email' => 'jane@example.com',
            'consent_client_address' => '123 Main Street',
            'fixed_fee_amount' => '25.00',
            'agency_fee_consent' => 1,
            'agency_fee_payment_method' => 'card',
            'agency_fee_amount_paid' => '25.00',
            'client_consent_signature_type' => 'typed',
            'client_consent_signed_by_last_name' => 'Roe',
            'client_consent_signed_at' => '2026-01-24 10:00:00',
            'client_consent_signature_file_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson("/api/client-consents/{$id}");
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Client consent deleted.');

        $this->assertDatabaseMissing('client_consents', ['id' => $id]);
    }
}
