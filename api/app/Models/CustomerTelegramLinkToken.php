<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTelegramLinkToken extends Model
{
    protected $fillable = [
        'customer_id',
        'token',
        'expires_at',
        'consumed_at',
        'telegram_user_id',
        'telegram_chat_id',
        'telegram_username',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
