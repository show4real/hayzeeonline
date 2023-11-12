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
}
