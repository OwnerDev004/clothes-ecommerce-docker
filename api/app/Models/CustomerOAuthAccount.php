<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOAuthAccount extends Model
{
    protected $table = 'customer_oauth_accounts';

    protected $fillable = [
        'customer_id',
        'provider',
        'provider_user_id',
        'email',
        'avatar_url',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
