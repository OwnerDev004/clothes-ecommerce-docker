<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'user_name' => $this->user_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar_url' => $this->avatar_url,
            'avatar_public_id' => $this->avatar_public_id,
            'oauth_accounts' => $this->whenLoaded('oauthAccounts', function () {
                return $this->oauthAccounts->map(fn($account) => [
                    'id' => $account->id,
                    'provider' => $account->provider,
                    'provider_user_id' => $account->provider_user_id,
                    'email' => $account->email,
                    'avatar_url' => $account->avatar_url,
                    'expires_at' => $account->expires_at,
                ])->values();
            }),
            'telegram_username' => $this->telegram_username,
            'enable_telegram_alerts' => (bool) $this->enable_telegram_alerts,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
