<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientConsentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'consentClientFullName' => ['required', 'string', 'max:255'],
            'consentClientPhone' => ['required', 'string', 'max:50'],
            'consentClientEmail' => ['required', 'email', 'max:255'],
            'consentClientAddress' => ['required', 'string', 'max:2000'],

            'fixedFeeAmount' => ['required', 'string', 'max:50'],

            // Sent as "true"/"false" strings in multipart/form-data.
            'agencyFeeConsent' => ['required', Rule::in(['true', 'false', '1', '0', 1, 0, true, false])],

            'agencyFeePaymentMethod' => ['nullable', 'string', 'max:100'],
            'agencyFeeAmountPaid' => ['nullable', 'string', 'max:50'],

            'clientConsentSignatureType' => ['required', Rule::in(['typed', 'upload'])],
            'clientConsentSignedByLastName' => ['nullable', 'string', 'max:255'],
            'clientConsentSignedAt' => ['required', 'date'],

            'clientConsentSignatureFile' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = (string) $this->input('clientConsentSignatureType');
            $hasFile = $this->hasFile('clientConsentSignatureFile');

            if ($type === 'upload' && ! $hasFile) {
                $validator->errors()->add('clientConsentSignatureFile', 'clientConsentSignatureFile is required when clientConsentSignatureType is upload.');
            }

            if ($type === 'typed' && $hasFile) {
                $validator->errors()->add('clientConsentSignatureFile', 'clientConsentSignatureFile must not be provided when clientConsentSignatureType is typed.');
            }
        });
    }
}
