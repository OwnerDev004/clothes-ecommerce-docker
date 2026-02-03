<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    protected $hidden = [
        "password"
    ];
    protected $fillable = [
        "full_name",
        "gender",
        "dob",
        "user_name",
        "email",
        "phone",
        "address",
        "password"
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
