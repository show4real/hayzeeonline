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
        'email',
        'residential_address',
        'years_at_address',
        'previous_address',
        'insurance_type',
        'carrier_name',
        'vehicle_vins',
        'insurance_expiration_date',
        'payment_method',
        'processing_officer_name',
        'valid_id_card_path',
        'previous_insurance_document_path',
    ];

    protected $casts = [
        'vehicle_vins' => 'array',
        'insurance_expiration_date' => 'date',
        'years_at_address' => 'integer',
    ];
}
