<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

     public function scopeReferrer($query, $filter)
    {
        if ($filter) {
            return $query->where('referrer_id', $filter);
        }
        return $query;
    }


      public function scopeSearch($query, $filter)
    {
        $searchQuery = trim($filter);
        $requestData = ['name', 'referral_code'];
        $query->when($filter != '', function ($query) use ($requestData, $searchQuery) {
            return $query->where(function ($q) use ($requestData, $searchQuery) {
                foreach ($requestData as $field)
                    $q->orWhere($field, 'like', "%{$searchQuery}%");
            })->orderByRaw("FIELD(availability,1) DESC")->orderBy("updated_at", "DESC");
        });
    }
}
