<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'attributes', 'created_at', 'image_url', 'updated_at'];

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

    public function products()
    {
        return $this->hasMany('App\Models\Product', 'category_id', 'id')->latest();
    }
}
