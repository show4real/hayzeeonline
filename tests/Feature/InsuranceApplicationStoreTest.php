<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InsuranceApplicationStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_insurance_application_with_files()
    {
        Storage::fake('public');

        $payload = [
            'firstName' => 'John',
            'middleName' => 'M',
            'lastName' => 'Doe',
            'maritalStatus' => 'Single',
            'spouseFullName' => 'Jane Doe',
            'spouseDOB' => '1990-05-19',
            'spouseDriversLicenseNumber' => 'D1234567',
            'spouseExcludedFromPolicy' => 'no',
            'email' => 'john@example.com',
            'residentialAddress' => '123 Main St',
            'yearsAtAddress' => 2,
            'previousAddress' => '456 Old St',
            'insuranceType' => 'Auto',
            'carrierName' => 'GEICO',
            // Legacy VIN field supports comma or newline separated.
            'vehicleVINs' => "VIN1\nVIN2",
            'insuranceExpirationDate' => '2026-02-01',
            'paymentMethod' => 'Auto Pay',
            'processingOfficerName' => 'Jane Smith',
            'validIdCard' => UploadedFile::fake()->image('id.png'),
            'previousInsuranceDocument' => UploadedFile::fake()->create('prev.pdf', 50, 'application/pdf'),
        ];

        $response = $this->postJson('/api/insurance-applications', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Insurance application submitted.');

        $this->assertDatabaseHas('insurance_applications', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'carrier_name' => 'GEICO',
            'spouse_full_name' => 'Jane Doe',
        ]);

        $app = \DB::table('insurance_applications')->first();
        $this->assertNotNull($app);

        Storage::disk('public')->assertExists($app->valid_id_card_path);
        Storage::disk('public')->assertExists($app->previous_insurance_document_path);
    }

    public function test_it_accepts_vehicles_payload_and_derives_vehicle_vins()
    {
        Storage::fake('public');

        $payload = [
            'firstName' => 'John',
            'middleName' => 'M',
            'lastName' => 'Doe',
            'maritalStatus' => 'Married',
            'email' => 'john@example.com',
            'residentialAddress' => '123 Main St',
            'yearsAtAddress' => 2,
            'insuranceType' => 'Auto',
            'carrierName' => 'GEICO',
            'vehicles' => [
                [
                    'vin' => '1HGCM82633A004352',
                    'make' => 'Honda',
                    'model' => 'Accord',
                    'year' => '2022',
                    'purchaseDate' => '2024-01-15',
                    'ownershipType' => 'owned',
                ],
                [
                    'vin' => '2C3KA53G76H123456',
                    'make' => 'Toyota',
                    'model' => 'Camry',
                    'year' => '2020',
                    'purchaseDate' => '2023-06-20',
                    'ownershipType' => 'lien',
                ],
            ],
            'insuranceExpirationDate' => '2026-09-30',
            'paymentMethod' => 'Auto Pay',
            'processingOfficerName' => 'Alex Smith',
            'validIdCard' => UploadedFile::fake()->image('id.png'),
            'previousInsuranceDocument' => UploadedFile::fake()->create('prev.pdf', 50, 'application/pdf'),
        ];

        $response = $this->postJson('/api/insurance-applications', $payload);
        $response->assertStatus(201);

        $app = \DB::table('insurance_applications')->first();
        $this->assertNotNull($app);

        $this->assertSame(
            json_encode(['1HGCM82633A004352', '2C3KA53G76H123456']),
            $app->vehicle_vins
        );

        $this->assertNotNull($app->vehicles);
    }

    public function test_it_rejects_missing_required_fields()
    {
        $response = $this->postJson('/api/insurance-applications', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'firstName',
            'lastName',
            'maritalStatus',
            'email',
            'residentialAddress',
            'yearsAtAddress',
            'insuranceType',
            'carrierName',
            'vehicleVINs',
            'insuranceExpirationDate',
            'paymentMethod',
            'processingOfficerName',
            'validIdCard',
            'previousInsuranceDocument',
        ]);
    }

    public function test_it_lists_stored_insurance_applications()
    {
        // Insert directly to avoid file upload dependency in this test.
        \DB::table('insurance_applications')->insert([
            'first_name' => 'John',
            'middle_name' => 'M',
            'last_name' => 'Doe',
            'marital_status' => 'Single',
            'email' => 'john@example.com',
            'residential_address' => '123 Main St',
            'years_at_address' => 2,
            'previous_address' => '456 Old St',
            'insurance_type' => 'Auto',
            'carrier_name' => 'GEICO',
            'vehicle_vins' => json_encode(['VIN1', 'VIN2']),
            'insurance_expiration_date' => '2026-02-01',
            'payment_method' => 'Auto Pay',
            'processing_officer_name' => 'Jane Smith',
            'valid_id_card_path' => 'insurance_applications/valid_id_cards/id.png',
            'previous_insurance_document_path' => 'insurance_applications/previous_insurance_documents/prev.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/insurance-applications?rows=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'applications' => [
                'data',
                'current_page',
                'per_page',
                'total',
            ],
        ]);
        $response->assertJsonPath('applications.data.0.email', 'john@example.com');
    }

    public function test_it_deletes_insurance_application()
    {
        $id = \DB::table('insurance_applications')->insertGetId([
            'first_name' => 'John',
            'middle_name' => 'M',
            'last_name' => 'Doe',
            'marital_status' => 'Single',
            'email' => 'john@example.com',
            'residential_address' => '123 Main St',
            'years_at_address' => 2,
            'previous_address' => '456 Old St',
            'insurance_type' => 'Auto',
            'carrier_name' => 'GEICO',
            'vehicle_vins' => json_encode(['VIN1', 'VIN2']),
            'insurance_expiration_date' => '2026-02-01',
            'payment_method' => 'Auto Pay',
            'processing_officer_name' => 'Jane Smith',
            'valid_id_card_path' => 'insurance_applications/valid_id_cards/id.png',
            'previous_insurance_document_path' => 'insurance_applications/previous_insurance_documents/prev.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/delete/insurance-applications/' . $id);
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Insurance application deleted.');

        $this->assertDatabaseMissing('insurance_applications', ['id' => $id]);
    }
}
