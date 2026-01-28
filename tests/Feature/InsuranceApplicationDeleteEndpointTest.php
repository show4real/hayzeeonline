<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceApplicationDeleteEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_insurance_application_via_delete_endpoint()
    {
        $id = \DB::table('insurance_applications')->insertGetId([
            'first_name' => 'John',
            'middle_name' => 'M',
            'last_name' => 'Doe',
            'marital_status' => 'Single',
            'email' => 'john@example.com',
            'phone' => null,
            'residential_address' => '123 Main St',
            'years_at_address' => 2,
            'previous_address' => '456 Old St',
            'insurance_type' => 'Auto',
            'carrier_name' => 'GEICO',
            'vehicle_vins' => json_encode(['VIN1', 'VIN2']),
            'insurance_expiration_date' => '2026-02-01',
            'payment_method' => 'Auto Pay',
            'processing_officer_name' => 'Jane Smith',
            'valid_id_card_path' => 'insurance/valid_id_cards/id.png',
            'previous_insurance_document_path' => 'insurance/previous_insurance_documents/prev.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson('/api/insurance-applications/' . $id);
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Insurance application deleted.');

        $this->assertDatabaseMissing('insurance_applications', ['id' => $id]);
    }
}
