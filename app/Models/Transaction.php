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

      public function referrer()
    {
        return $this->belongsTo('App\Models\Referrer', 'referrer_id');
    }





     public function scopeSearch($query, $filter)
    {
    	$searchQuery = trim($filter);
    	$requestData = ['paid','percentage'];
        $referrerData = ['name','referral_code'];
    	$query->when($filter!='', function ($query) use($requestData,$referrerData, $searchQuery) {
    		return $query->where(function($q) use($requestData, $searchQuery) {
    			foreach ($requestData as $field)
    				$q->orWhere($field, 'like', "%{$searchQuery}%");
    			})->orWhere(function($qq) use($referrerData, $searchQuery) {
                    foreach ($referrerData as $field)
                        $qq->orWhereHas('referrer', function($qqq) use($referrerData, $searchQuery, $field) {
                            $qqq->where($field, 'like', "%{$searchQuery}%");
                        });
                    });
    	});
    }
}
