<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Referrer;

class Transaction extends Model
{
    use HasFactory;

    protected $appends = ['name','code','approver'];

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


     public function getNameAttribute()
    {
        $referrer = Referrer::where('id', $this->referrer_id)->first();
        if ($referrer) {
            return $referrer->name;
        }
    }

     public function getApproverAttribute()
    {
        $user = User::where('id', $this->approved_by)->first();
        if ($user) {
            return $user->name;
        }
    }

     public function getCodeAttribute()
    {
        $referrer = Referrer::where('id', $this->referrer_id)->first();
        if ($referrer) {
            return $referrer->referral_code;
        }
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

    public function scopeFilter1($query, $filter){
        if($filter != null){
         return $query->where("created_at",'>',$filter)->latest();
        }
    }

    public function scopeFilter2($query, $filter){
        if($filter != null){
         return $query->where("created_at",'<',$filter)->latest();
        }
    }
}
