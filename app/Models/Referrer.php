<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referrer extends Model
{
    use HasFactory;

    protected $appends = ['email', 'phone'];

      public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

     public function getEmailAttribute()
    {
        $user = User::where('id', $this->user_id)->first();
        if ($user) {
            return $user->email;
        }
    }

    public function getPhoneAttribute()
    {
        $user = User::where('id', $this->user_id)->first();
        if ($user) {
            return $user->phone;
        }
    }

    public function scopeSearch($query, $filter)
    {
        $searchQuery = trim($filter);
        $requestData = ['name', 'referral_code'];
        $query->when($filter != '', function ($query) use ($requestData, $searchQuery) {
            return $query->where(function ($q) use ($requestData, $searchQuery) {
                foreach ($requestData as $field)
                    $q->orWhere($field, 'like', "%{$searchQuery}%");
            })->orderBy("updated_at", "DESC");
        });
    }
}
