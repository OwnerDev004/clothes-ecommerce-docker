<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Notifications\CustomerResetPasswordNotification;

class Customer extends Authenticatable implements JWTSubject
{
    use Notifiable;

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
        "password",
        "avatar_url",
        "avatar_public_id"
    ];

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(CustomerOAuthAccount::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function voucherUses(): HasMany
    {
        return $this->hasMany(VoucherUse::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
        ];
    }
}
