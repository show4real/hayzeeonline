<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'address',
        'admin'
    ];



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

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

    /**
     * Filter users by role. Accepts a role name (customer, admin, staff,
     * referrer) or the raw numeric "admin" value.
     */
    public function scopeFilterRole($query, $role)
    {
        if ($role === null || $role === '') {
            return $query;
        }

        $map = [
            'customer' => 0,
            'admin' => 1,
            'staff' => 2,
            'referrer' => null,
        ];

        $key = is_string($role) ? strtolower(trim($role)) : $role;

        if (is_string($key) && array_key_exists($key, $map)) {
            $value = $map[$key];
        } else {
            $value = is_numeric($key) ? (int) $key : $key;
        }

        return is_null($value)
            ? $query->whereNull('admin')
            : $query->where('admin', $value);
    }
}