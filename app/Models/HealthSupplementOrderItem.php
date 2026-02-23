<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthSupplementOrderItem extends Model
{
    use HasFactory;

    protected $table = 'health_supplement_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'price',
        'quantity',
        'total',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(HealthSupplementProduct::class, 'product_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(HealthSupplementOrder::class, 'order_id');
    }
}
