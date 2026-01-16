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
            'email' => ['required', 'email', 'max:255'],
            'residentialAddress' => ['required', 'string', 'max:1000'],
            'yearsAtAddress' => ['required', 'integer', 'min:0', 'max:120'],
            'previousAddress' => ['nullable', 'string', 'max:1000'],
            'insuranceType' => ['required', 'string', 'max:100'],
            'carrierName' => ['required', 'string', 'max:255'],

            // Accept either an array of VINs or a comma-separated list.
            'vehicleVINs' => ['required'],

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
            'vehicleVINs.required' => 'vehicleVINs is required (array or comma-separated string).',
        ];
    }
}
