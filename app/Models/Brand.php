<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'image_url', 'created_at', 'updated_at'];

    public function scopeSearchAll($query, $filter)
    {
        $searchQuery = trim($filter);
        $requestData = ['name'];
        $query->when($filter != '', function ($query) use ($requestData, $searchQuery) {
            return $query->where(function ($q) use ($requestData, $searchQuery) {
                foreach ($requestData as $field)
                    $q->orWhere($field, 'like', "%{$searchQuery}%");
            });
        });
    }
}
