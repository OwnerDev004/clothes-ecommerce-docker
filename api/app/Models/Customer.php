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
        "avatar_public_id",
        "telegram_user_id",
        "telegram_chat_id",
        "telegram_username",
        "enable_telegram_alerts",
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

    public function telegramLinkTokens(): HasMany
    {
        return $this->hasMany(CustomerTelegramLinkToken::class);
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

    public function routeNotificationForTelegram(): ?string
    {
        if ($this->telegram_chat_id) {
            return $this->telegram_chat_id;
        }

        if ($this->telegram_user_id) {
            return $this->telegram_user_id;
        }

        if ($this->telegram_username) {
            $username = ltrim($this->telegram_username, '@');
            return $username !== '' ? '@' . $username : null;
        }

        return null;
    }

    public function requiresProfileCompletion(): bool
    {
        if (!$this->oauthAccounts()->exists()) {
            return false;
        }

        return $this->telegram_username === null || $this->telegram_username === '';
    }
}
