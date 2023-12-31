<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceEdit extends Model
{
    use HasFactory;

    protected $table = "priceedits";

    protected $fillable = ["percentage", "start_date", "end_date", "comment"];
}
