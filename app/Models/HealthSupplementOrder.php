<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthSupplementOrder extends Model
{
    use HasFactory;

    protected $table = 'health_supplement_orders';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'total_price',
        'status',
        'payment_reference',
        'payment_provider',
        'payment_status',
        'notes',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HealthSupplementOrderItem::class, 'order_id');
    }
}
