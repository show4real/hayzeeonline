<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Order extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'total_price', 'status', 'description','payment_reference','discount'];
    
     public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }
    
     public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    
    public function scopeSearchAll($query, $filter)
    {
    	$searchQuery = trim($filter);
    	$requestData = ['description','id'];
    	$query->when($filter!='', function ($query) use($requestData, $searchQuery) {
    		return $query->where(function($q) use($requestData, $searchQuery) {
    			foreach ($requestData as $field)
    				$q->orWhere($field, 'like', "%{$searchQuery}%");
    			});
    	});
    }
}