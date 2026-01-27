<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'marital_status',

    'spouse_full_name',
    'spouse_dob',
    'spouse_drivers_license_number',
    'spouse_excluded_from_policy',

        'email',
        'residential_address',
        'years_at_address',
        'previous_address',
        'insurance_type',
        'carrier_name',

    'vehicles',
        'vehicle_vins',
        'insurance_expiration_date',
        'payment_method',
        'processing_officer_name',
        'valid_id_card_path',
        'previous_insurance_document_path',
    ];

    protected $casts = [
    'vehicles' => 'array',
        'vehicle_vins' => 'array',
    'spouse_dob' => 'date',
        'insurance_expiration_date' => 'date',
        'years_at_address' => 'integer',
    ];
}
