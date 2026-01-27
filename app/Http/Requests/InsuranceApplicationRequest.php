<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceApplicationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'maritalStatus' => ['required', 'string', 'max:50'],

            // Spouse info (optional)
            'spouseFullName' => ['nullable', 'string', 'max:255'],
            'spouseDOB' => ['nullable', 'date_format:Y-m-d'],
            'spouseDriversLicenseNumber' => ['nullable', 'string', 'max:255'],
            'spouseExcludedFromPolicy' => ['nullable', 'string', 'max:50'],

            'email' => ['required', 'email', 'max:255'],
            'residentialAddress' => ['required', 'string', 'max:1000'],
            'yearsAtAddress' => ['required', 'integer', 'min:0', 'max:120'],
            'previousAddress' => ['nullable', 'string', 'max:1000'],
            'insuranceType' => ['required', 'string', 'max:100'],
            'carrierName' => ['required', 'string', 'max:255'],

            // New: vehicles payload can be provided either as array (application/json)
            // or as a JSON string (multipart/form-data).
            'vehicles' => ['nullable'],

            // Legacy: accept array of VINs OR a comma/newline-separated string.
            // Required if vehicles isn't provided.
            'vehicleVINs' => ['required_without:vehicles'],

            'insuranceExpirationDate' => ['required', 'date_format:Y-m-d'],
            'paymentMethod' => ['required', 'string', 'max:100'],
            'processingOfficerName' => ['required', 'string', 'max:255'],

            'validIdCard' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'previousInsuranceDocument' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function messages()
    {
        return [
            'vehicleVINs.required_without' => 'vehicleVINs is required when vehicles is not provided.',
        ];
    }
}
