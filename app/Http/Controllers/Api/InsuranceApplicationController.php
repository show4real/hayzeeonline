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

        $vehicleVins = $data['vehicleVINs'];
        if (is_string($vehicleVins)) {
            $vehicleVins = array_values(array_filter(array_map('trim', explode(',', $vehicleVins))));
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
            'email' => Str::lower($data['email']),
            'residential_address' => $data['residentialAddress'],
            'years_at_address' => (int) $data['yearsAtAddress'],
            'previous_address' => $data['previousAddress'] ?? null,
            'insurance_type' => $data['insuranceType'],
            'carrier_name' => $data['carrierName'],
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
}
