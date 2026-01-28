<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientConsentRequest;
use App\Models\ClientConsent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientConsentController extends Controller
{
    public function index()
    {
        $rows = (int) request()->input('rows', 15);
        $rows = $rows > 0 ? min($rows, 100) : 15;

        $search = trim((string) request()->input('search', ''));
        $agencyFeeConsent = request()->input('agencyFeeConsent');
        $signatureType = request()->input('signatureType');
        $signedFrom = request()->input('signedFrom');
        $signedTo = request()->input('signedTo');

        $query = ClientConsent::query()->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('consent_client_full_name', 'like', "%{$search}%")
                    ->orWhere('consent_client_phone', 'like', "%{$search}%")
                    ->orWhere('consent_client_email', 'like', "%{$search}%")
                    ->orWhere('consent_client_address', 'like', "%{$search}%")
                    ->orWhere('client_consent_signed_by_last_name', 'like', "%{$search}%")
                    ->orWhere('agency_fee_payment_method', 'like', "%{$search}%")
                    ->orWhere('agency_fee_amount_paid', 'like', "%{$search}%")
                    ->orWhere('fixed_fee_amount', 'like', "%{$search}%");
            });
        }

        if ($agencyFeeConsent !== null && $agencyFeeConsent !== '') {
            $bool = filter_var($agencyFeeConsent, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                $query->where('agency_fee_consent', $bool);
            }
        }

        if (is_string($signatureType) && $signatureType !== '') {
            $query->where('client_consent_signature_type', $signatureType);
        }

        if ($signedFrom) {
            $query->where('client_consent_signed_at', '>=', $signedFrom);
        }

        if ($signedTo) {
            $query->where('client_consent_signed_at', '<=', $signedTo);
        }

        $consents = $query->paginate($rows);

        return response()->json(compact('consents'));
    }

    public function store(ClientConsentRequest $request)
    {
        $data = $request->validated();

        $signatureType = $data['clientConsentSignatureType'];

        $signatureFileUrl = null;
        if ($signatureType === 'upload' && $request->hasFile('clientConsentSignatureFile')) {
            $consentsDir = public_path('client_consents');
            if (! File::exists($consentsDir)) {
                File::makeDirectory($consentsDir, 0755, true);
            }

            $file = $request->file('clientConsentSignatureFile');
            $fileName = time() . '_' . Str::random(10) . '_signature.' . $file->getClientOriginalExtension();
            $file->move($consentsDir, $fileName);
            $signatureFileUrl = URL::asset('client_consents/' . $fileName);
        }

        $consent = ClientConsent::create([
            'consent_client_full_name' => $data['consentClientFullName'],
            'consent_client_phone' => $data['consentClientPhone'],
            'consent_client_email' => Str::lower($data['consentClientEmail']),
            'consent_client_address' => $data['consentClientAddress'],

            'fixed_fee_amount' => $data['fixedFeeAmount'],
            'agency_fee_consent' => filter_var($data['agencyFeeConsent'], FILTER_VALIDATE_BOOLEAN),
            'agency_fee_payment_method' => $data['agencyFeePaymentMethod'] ?? null,
            'agency_fee_amount_paid' => $data['agencyFeeAmountPaid'] ?? null,

            'client_consent_signature_type' => $signatureType,
            'client_consent_signed_by_last_name' => $data['clientConsentSignedByLastName'],
            'client_consent_signed_at' => $data['clientConsentSignedAt'],

            'client_consent_signature_file_path' => $signatureFileUrl,
        ]);

        return response()->json([
            'message' => 'Client consent submitted.',
            'data' => $consent,
        ], 201);
    }

    public function update(Request $request, ClientConsent $clientConsent)
    {
        $data = $request->validate([
            'consentClientFullName' => ['sometimes', 'string', 'max:255'],
            'consentClientPhone' => ['sometimes', 'string', 'max:50'],
            'consentClientEmail' => ['sometimes', 'email', 'max:255'],
            'consentClientAddress' => ['sometimes', 'string', 'max:2000'],

            'fixedFeeAmount' => ['sometimes', 'string', 'max:50'],
            // Sent as "true"/"false" strings in multipart/form-data.
            'agencyFeeConsent' => ['sometimes', \Illuminate\Validation\Rule::in(['true', 'false', '1', '0', 1, 0, true, false])],
            'agencyFeePaymentMethod' => ['nullable', 'string', 'max:100'],
            'agencyFeeAmountPaid' => ['nullable', 'string', 'max:50'],

            'clientConsentSignatureType' => ['sometimes', \Illuminate\Validation\Rule::in(['typed', 'upload'])],
            'clientConsentSignedByLastName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'clientConsentSignedAt' => ['sometimes', 'date'],

            'clientConsentSignatureFile' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $signatureType = $data['clientConsentSignatureType'] ?? $clientConsent->client_consent_signature_type;
        $hasFile = $request->hasFile('clientConsentSignatureFile');

        if ($signatureType === 'upload' && $request->has('clientConsentSignatureType') && ! $hasFile) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => ['clientConsentSignatureFile' => ['clientConsentSignatureFile is required when clientConsentSignatureType is upload.']],
            ], 422);
        }

        if ($signatureType === 'typed' && $hasFile) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => ['clientConsentSignatureFile' => ['clientConsentSignatureFile must not be provided when clientConsentSignatureType is typed.']],
            ], 422);
        }

        $signatureFileUrl = $clientConsent->client_consent_signature_file_path;
        if ($signatureType === 'upload' && $hasFile) {
            $consentsDir = public_path('client_consents');
            if (! File::exists($consentsDir)) {
                File::makeDirectory($consentsDir, 0755, true);
            }

            $file = $request->file('clientConsentSignatureFile');
            $fileName = time() . '_' . Str::random(10) . '_signature.' . $file->getClientOriginalExtension();
            $file->move($consentsDir, $fileName);
            $signatureFileUrl = URL::asset('client_consents/' . $fileName);
        }

        $clientConsent->fill([
            'consent_client_full_name' => $data['consentClientFullName'] ?? $clientConsent->consent_client_full_name,
            'consent_client_phone' => $data['consentClientPhone'] ?? $clientConsent->consent_client_phone,
            'consent_client_email' => array_key_exists('consentClientEmail', $data)
                ? Str::lower($data['consentClientEmail'])
                : $clientConsent->consent_client_email,
            'consent_client_address' => $data['consentClientAddress'] ?? $clientConsent->consent_client_address,

            'fixed_fee_amount' => $data['fixedFeeAmount'] ?? $clientConsent->fixed_fee_amount,
            'agency_fee_consent' => array_key_exists('agencyFeeConsent', $data)
                ? filter_var($data['agencyFeeConsent'], FILTER_VALIDATE_BOOLEAN)
                : $clientConsent->agency_fee_consent,
            'agency_fee_payment_method' => array_key_exists('agencyFeePaymentMethod', $data)
                ? ($data['agencyFeePaymentMethod'] ?? null)
                : $clientConsent->agency_fee_payment_method,
            'agency_fee_amount_paid' => array_key_exists('agencyFeeAmountPaid', $data)
                ? ($data['agencyFeeAmountPaid'] ?? null)
                : $clientConsent->agency_fee_amount_paid,

            'client_consent_signature_type' => $signatureType,
            'client_consent_signed_by_last_name' => array_key_exists('clientConsentSignedByLastName', $data)
                ? ($data['clientConsentSignedByLastName'] ?? null)
                : $clientConsent->client_consent_signed_by_last_name,
            'client_consent_signed_at' => $data['clientConsentSignedAt'] ?? $clientConsent->client_consent_signed_at,

            'client_consent_signature_file_path' => $signatureFileUrl,
        ]);

        $clientConsent->save();

        return response()->json([
            'message' => 'Client consent updated.',
            'data' => $clientConsent->fresh(),
        ]);
    }
}
