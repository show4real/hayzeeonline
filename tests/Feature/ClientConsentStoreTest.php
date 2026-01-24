<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ClientConsentStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_typed_client_consent_without_file()
    {
        $payload = [
            'consentClientFullName' => 'Jane Roe',
            'consentClientPhone' => '555-1234',
            'consentClientEmail' => 'JANE@EXAMPLE.COM',
            'consentClientAddress' => '123 Main Street',
            'fixedFeeAmount' => '25.00',
            'agencyFeeConsent' => 'true',
            'agencyFeePaymentMethod' => 'card',
            'agencyFeeAmountPaid' => '25.00',
            'clientConsentSignatureType' => 'typed',
            'clientConsentSignedByLastName' => 'Roe',
            'clientConsentSignedAt' => '2026-01-24T10:00:00Z',
        ];

        $response = $this->postJson('/api/client-consents', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Client consent submitted.');

        $this->assertDatabaseHas('client_consents', [
            'consent_client_full_name' => 'Jane Roe',
            'consent_client_email' => 'jane@example.com',
            'agency_fee_consent' => 1,
            'client_consent_signature_type' => 'typed',
        ]);
    }

    public function test_it_requires_file_when_signature_type_is_upload()
    {
        $payload = [
            'consentClientFullName' => 'Jane Roe',
            'consentClientPhone' => '555-1234',
            'consentClientEmail' => 'jane@example.com',
            'consentClientAddress' => '123 Main Street',
            'fixedFeeAmount' => '25.00',
            'agencyFeeConsent' => 'false',
            'clientConsentSignatureType' => 'upload',
            'clientConsentSignedByLastName' => 'Roe',
            'clientConsentSignedAt' => '2026-01-24T10:00:00Z',
        ];

        $response = $this->postJson('/api/client-consents', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['clientConsentSignatureFile']);
    }

    public function test_it_stores_upload_signature_file()
    {
        // Note: controller stores into /public/client_consents via move(), so we assert DB value is not null.
        $payload = [
            'consentClientFullName' => 'Jane Roe',
            'consentClientPhone' => '555-1234',
            'consentClientEmail' => 'jane@example.com',
            'consentClientAddress' => '123 Main Street',
            'fixedFeeAmount' => '25.00',
            'agencyFeeConsent' => 'true',
            'clientConsentSignatureType' => 'upload',
            'clientConsentSignedByLastName' => 'Roe',
            'clientConsentSignedAt' => '2026-01-24T10:00:00Z',
            'clientConsentSignatureFile' => UploadedFile::fake()->image('sig.png'),
        ];

        $response = $this->postJson('/api/client-consents', $payload);
        $response->assertStatus(201);

        $row = \DB::table('client_consents')->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->client_consent_signature_file_path);
    }

    public function test_it_lists_consents_with_search_and_filters()
    {
        \DB::table('client_consents')->insert([
            [
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
            ],
            [
                'consent_client_full_name' => 'John Doe',
                'consent_client_phone' => '555-9999',
                'consent_client_email' => 'john@example.com',
                'consent_client_address' => '456 Other Ave',
                'fixed_fee_amount' => '25.00',
                'agency_fee_consent' => 0,
                'agency_fee_payment_method' => null,
                'agency_fee_amount_paid' => null,
                'client_consent_signature_type' => 'upload',
                'client_consent_signed_by_last_name' => 'Doe',
                'client_consent_signed_at' => '2026-01-23 10:00:00',
                'client_consent_signature_file_path' => 'http://example.test/client_consents/sig.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/client-consents?rows=10&search=jane&agencyFeeConsent=true&signatureType=typed');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'consents' => [
                'data',
                'current_page',
                'per_page',
                'total',
            ],
        ]);
        $response->assertJsonPath('consents.total', 1);
        $response->assertJsonPath('consents.data.0.consent_client_email', 'jane@example.com');
    }
}
