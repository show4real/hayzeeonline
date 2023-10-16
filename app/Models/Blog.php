<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;


    protected $fillable = ['name', 'slug', 'created_at', 'image', 'description', 'updated_at'];

    protected $appends = ['month'];
    public function scopeSearch($query, $filter)
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

    public function getMonthAttribute()
    {
        $createdAt = $this->created_at;
        return $createdAt->diffForHumans();
    }
}