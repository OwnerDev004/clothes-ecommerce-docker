<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTelegramLinkToken;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramLinkController extends Controller
{
    use ApiResponse;

    public function createLink(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $botUsername = ltrim(trim((string) config('services.telegram-bot-api.username', '')), '@');
        if ($botUsername === '') {
            return $this->error('Telegram bot username is not configured', 422);
        }

        $ttlMinutes = (int) config('services.telegram-bot-api.link_ttl_minutes', 10);
        if ($ttlMinutes <= 0) {
            $ttlMinutes = 10;
        }

        CustomerTelegramLinkToken::where('customer_id', $customer->id)
            ->whereNull('consumed_at')
            ->delete();

        $token = $this->generateToken();
        $linkToken = CustomerTelegramLinkToken::create([
            'customer_id' => $customer->id,
            'token' => $token,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        $deepLink = "https://t.me/{$botUsername}?start={$token}";

        return $this->success([
            'token' => $token,
            'deep_link' => $deepLink,
            'expires_at' => $linkToken->expires_at?->toISOString(),
            'instructions' => 'Open the link and tap Start in Telegram to connect your account.',
        ], 'Telegram link created');
    }

    public function webhook(Request $request, string $secret)
    {
        $expectedSecret = trim((string) config('services.telegram-bot-api.webhook_secret', ''));
        if ($expectedSecret === '' || !hash_equals($expectedSecret, $secret)) {
            return $this->error('Forbidden', 403);
        }

        $message = $request->input('message') ?? $request->input('edited_message');
        if (!is_array($message)) {
            return $this->success(['processed' => false], 'Ignored');
        }

        $text = trim((string) ($message['text'] ?? ''));
        if ($text === '' || !str_starts_with($text, '/start')) {
            return $this->success(['processed' => false], 'Ignored');
        }

        if (!preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9_-]{10,64})$/', $text, $matches)) {
            $this->sendTelegramMessage((string) data_get($message, 'chat.id', ''), 'Invalid link token. Please reconnect from your account page.');
            return $this->success(['processed' => false], 'Invalid token payload');
        }

        $token = $matches[1];
        $telegramUserId = (string) data_get($message, 'from.id', '');
        $telegramChatId = (string) data_get($message, 'chat.id', '');
        $telegramUsername = data_get($message, 'from.username');

        if ($telegramUserId === '' || $telegramChatId === '') {
            return $this->success(['processed' => false], 'Missing Telegram identifiers');
        }

        DB::transaction(function () use ($token, $telegramUserId, $telegramChatId, $telegramUsername) {
            $linkToken = CustomerTelegramLinkToken::where('token', $token)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (!$linkToken) {
                $this->sendTelegramMessage($telegramChatId, 'Link token is invalid or expired. Please generate a new link from your account.');
                return;
            }

            $customer = Customer::whereKey($linkToken->customer_id)->lockForUpdate()->first();
            if (!$customer) {
                $this->sendTelegramMessage($telegramChatId, 'Account not found. Please try again.');
                return;
            }

            $alreadyLinked = Customer::where('telegram_user_id', $telegramUserId)
                ->where('id', '!=', $customer->id)
                ->exists();

            if ($alreadyLinked) {
                $this->sendTelegramMessage($telegramChatId, 'This Telegram account is already linked to another customer.');
                return;
            }

            $customer->telegram_user_id = $telegramUserId;
            $customer->telegram_chat_id = $telegramChatId;
            $customer->telegram_username = $telegramUsername ? (string) $telegramUsername : null;
            $customer->save();

            $linkToken->consumed_at = now();
            $linkToken->telegram_user_id = $telegramUserId;
            $linkToken->telegram_chat_id = $telegramChatId;
            $linkToken->telegram_username = $telegramUsername ? (string) $telegramUsername : null;
            $linkToken->save();

            $this->sendTelegramMessage($telegramChatId, 'Your Telegram account is now connected successfully.');
        });

        return $this->success(['processed' => true], 'Webhook processed');
    }

    private function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(32));
        } while (CustomerTelegramLinkToken::where('token', $token)->exists());

        return $token;
    }

    private function sendTelegramMessage(string $chatId, string $text): void
    {
        $botToken = trim((string) config('services.telegram-bot-api.token', ''));
        if ($chatId === '' || $botToken === '') {
            return;
        }

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed sending Telegram response message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
