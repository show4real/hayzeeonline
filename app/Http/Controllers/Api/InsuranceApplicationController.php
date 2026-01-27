<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceApplicationRequest;
use App\Models\InsuranceApplication;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;

class InsuranceApplicationController extends Controller
{
    public function show($id)
    {
        $application = InsuranceApplication::find($id);
        if (! $application) {
            return response()->json(['message' => 'Insurance application not found.'], 404);
        }

        return response()->json(compact('application'));
    }

    public function index()
    {
        $rows = (int) request()->input('rows', 15);
        $rows = $rows > 0 ? min($rows, 100) : 15;

        $search = trim((string) request()->input('search', ''));

        $query = InsuranceApplication::query()->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('carrier_name', 'like', "%{$search}%")
                    ->orWhere('insurance_type', 'like', "%{$search}%")
                    // MySQL/SQLite: basic string match on JSON column
                    ->orWhere('vehicle_vins', 'like', "%{$search}%");
            });
        }

        $applications = $query->paginate($rows);

        return response()->json(compact('applications'));
    }

    public function store(InsuranceApplicationRequest $request)
    {
        $data = $request->validated();

        // Accept vehicles as:
        // - array (application/json)
        // - JSON string (multipart/form-data)
        $vehicles = null;
        if (array_key_exists('vehicles', $data) && $data['vehicles'] !== null) {
            $vehicles = $data['vehicles'];
            if (is_string($vehicles)) {
                $decoded = json_decode($vehicles, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $vehicles = $decoded;
                }
            }
        }

        // VINs are stored as an array for multiple vehicles.
        // Priority: vehicles[].vin -> legacy vehicleVINs.
        $vehicleVins = [];

        if (is_array($vehicles)) {
            $vehicleVins = array_values(array_filter(array_map(function ($v) {
                if (! is_array($v)) {
                    return null;
                }
                $vin = isset($v['vin']) ? trim((string) $v['vin']) : '';
                return $vin !== '' ? $vin : null;
            }, $vehicles)));
        }

        if (count($vehicleVins) === 0 && array_key_exists('vehicleVINs', $data)) {
            $legacy = $data['vehicleVINs'];
            if (is_array($legacy)) {
                $vehicleVins = array_values(array_filter(array_map(fn ($vin) => trim((string) $vin), $legacy)));
            } else {
                // Support comma-separated or newline-separated VINs
                $legacy = str_replace(["\r\n", "\r"], "\n", (string) $legacy);
                $legacy = str_replace(',', "\n", $legacy);
                $vehicleVins = array_values(array_filter(array_map('trim', explode("\n", $legacy))));
            }
        }

        // Match existing productTrait style: store straight into /public.
        $insuranceDir = public_path('insurance');
        if (! File::exists($insuranceDir)) {
            File::makeDirectory($insuranceDir, 0755, true);
        }

        $validId = $request->file('validIdCard');
        $prevDoc = $request->file('previousInsuranceDocument');

        $validIdName = time() . '_' . Str::random(8) . '_valid_id.' . $validId->getClientOriginalExtension();
        $prevDocName = time() . '_' . Str::random(8) . '_previous_insurance.' . $prevDoc->getClientOriginalExtension();

        $validId->move($insuranceDir, $validIdName);
        $prevDoc->move($insuranceDir, $prevDocName);

        $validIdCardPath = URL::asset('insurance/' . $validIdName);
        $previousInsuranceDocumentPath = URL::asset('insurance/' . $prevDocName);

        $application = InsuranceApplication::create([
            'first_name' => $data['firstName'],
            'middle_name' => $data['middleName'] ?? null,
            'last_name' => $data['lastName'],
            'marital_status' => $data['maritalStatus'],

            'spouse_full_name' => $data['spouseFullName'] ?? null,
            'spouse_dob' => $data['spouseDOB'] ?? null,
            'spouse_drivers_license_number' => $data['spouseDriversLicenseNumber'] ?? null,
            'spouse_excluded_from_policy' => $data['spouseExcludedFromPolicy'] ?? null,

            'email' => Str::lower($data['email']),
            'residential_address' => $data['residentialAddress'],
            'years_at_address' => (int) $data['yearsAtAddress'],
            'previous_address' => $data['previousAddress'] ?? null,
            'insurance_type' => $data['insuranceType'],
            'carrier_name' => $data['carrierName'],
            'vehicles' => is_array($vehicles) ? $vehicles : null,
            'vehicle_vins' => $vehicleVins,
            'insurance_expiration_date' => $data['insuranceExpirationDate'],
            'payment_method' => $data['paymentMethod'],
            'processing_officer_name' => $data['processingOfficerName'],
            'valid_id_card_path' => $validIdCardPath,
            'previous_insurance_document_path' => $previousInsuranceDocumentPath,
        ]);

        return response()->json([
            'message' => 'Insurance application submitted.',
            'data' => $application,
        ], 201);
    }

    public function update(InsuranceApplicationRequest $request, $id)
    {
        $application = InsuranceApplication::find($id);
        if (! $application) {
            return response()->json(['message' => 'Insurance application not found.'], 404);
        }

        $data = $request->validated();

        // Parse vehicles (array or JSON string)
        $vehicles = null;
        if (array_key_exists('vehicles', $data) && $data['vehicles'] !== null) {
            $vehicles = $data['vehicles'];
            if (is_string($vehicles)) {
                $decoded = json_decode($vehicles, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $vehicles = $decoded;
                }
            }
        }

        // Derive VINs (vehicles[].vin preferred)
        $vehicleVins = [];
        if (is_array($vehicles)) {
            $vehicleVins = array_values(array_filter(array_map(function ($v) {
                if (! is_array($v)) {
                    return null;
                }
                $vin = isset($v['vin']) ? trim((string) $v['vin']) : '';
                return $vin !== '' ? $vin : null;
            }, $vehicles)));
        }

        if (count($vehicleVins) === 0 && array_key_exists('vehicleVINs', $data)) {
            $legacy = $data['vehicleVINs'];
            if (is_array($legacy)) {
                $vehicleVins = array_values(array_filter(array_map(fn ($vin) => trim((string) $vin), $legacy)));
            } else {
                $legacy = str_replace(["\r\n", "\r"], "\n", (string) $legacy);
                $legacy = str_replace(',', "\n", $legacy);
                $vehicleVins = array_values(array_filter(array_map('trim', explode("\n", $legacy))));
            }
        }

        // Optional file replacement
        $insuranceDir = public_path('insurance');
        if (! File::exists($insuranceDir)) {
            File::makeDirectory($insuranceDir, 0755, true);
        }

        $validIdCardPath = $application->valid_id_card_path;
        $previousInsuranceDocumentPath = $application->previous_insurance_document_path;

        if ($request->hasFile('validIdCard')) {
            $validId = $request->file('validIdCard');
            $validIdName = time() . '_' . Str::random(8) . '_valid_id.' . $validId->getClientOriginalExtension();
            $validId->move($insuranceDir, $validIdName);
            $validIdCardPath = URL::asset('insurance/' . $validIdName);
        }

        if ($request->hasFile('previousInsuranceDocument')) {
            $prevDoc = $request->file('previousInsuranceDocument');
            $prevDocName = time() . '_' . Str::random(8) . '_previous_insurance.' . $prevDoc->getClientOriginalExtension();
            $prevDoc->move($insuranceDir, $prevDocName);
            $previousInsuranceDocumentPath = URL::asset('insurance/' . $prevDocName);
        }

        $application->update([
            'first_name' => $data['firstName'],
            'middle_name' => $data['middleName'] ?? null,
            'last_name' => $data['lastName'],
            'marital_status' => $data['maritalStatus'],

            'spouse_full_name' => $data['spouseFullName'] ?? null,
            'spouse_dob' => $data['spouseDOB'] ?? null,
            'spouse_drivers_license_number' => $data['spouseDriversLicenseNumber'] ?? null,
            'spouse_excluded_from_policy' => $data['spouseExcludedFromPolicy'] ?? null,

            'email' => Str::lower($data['email']),
            'residential_address' => $data['residentialAddress'],
            'years_at_address' => (int) $data['yearsAtAddress'],
            'previous_address' => $data['previousAddress'] ?? null,
            'insurance_type' => $data['insuranceType'],
            'carrier_name' => $data['carrierName'],
            'vehicles' => is_array($vehicles) ? $vehicles : $application->vehicles,
            'vehicle_vins' => $vehicleVins,
            'insurance_expiration_date' => $data['insuranceExpirationDate'],
            'payment_method' => $data['paymentMethod'],
            'processing_officer_name' => $data['processingOfficerName'],
            'valid_id_card_path' => $validIdCardPath,
            'previous_insurance_document_path' => $previousInsuranceDocumentPath,
        ]);

        return response()->json([
            'message' => 'Insurance application updated.',
            'data' => $application->fresh(),
        ]);
    }
}
