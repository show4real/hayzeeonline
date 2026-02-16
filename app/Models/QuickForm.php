<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickForm extends Model
{
    protected $table = 'quick_forms';

    protected $fillable = [
        'name',
        'description',
        'id_card_path',
    ];
}
