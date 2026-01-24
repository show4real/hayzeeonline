<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'consent_client_full_name',
        'consent_client_phone',
        'consent_client_email',
        'consent_client_address',
        'fixed_fee_amount',
        'agency_fee_consent',
        'agency_fee_payment_method',
        'agency_fee_amount_paid',
        'client_consent_signature_type',
        'client_consent_signed_by_last_name',
        'client_consent_signed_at',
        'client_consent_signature_file_path',
    ];

    protected $casts = [
        'agency_fee_consent' => 'boolean',
        'client_consent_signed_at' => 'datetime',
    ];
}
