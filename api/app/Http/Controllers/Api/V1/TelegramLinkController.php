<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerTelegramLinkToken;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TelegramLinkController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: '/api/v1/telegram/connect-link',
        tags: ['Telegram'],
        summary: 'Create Telegram connect link',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Telegram link created'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Configuration error'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/telegram/webhook/{secret}',
        tags: ['Telegram'],
        summary: 'Telegram webhook',
        parameters: [
            new OA\Parameter(name: 'secret', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Webhook processed'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
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
            $customer->enable_telegram_alerts = true;
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

    #[OA\Post(
        path: '/api/v1/telegram/poll-link',
        tags: ['Telegram'],
        summary: 'Poll Telegram link status',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Poll result'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Configuration error'),
            new OA\Response(response: 502, description: 'Telegram upstream error'),
        ]
    )]
    public function pollLink(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        if ($customer->telegram_chat_id || $customer->telegram_user_id) {
            return $this->success(['linked' => true], 'Telegram already linked');
        }

        $botToken = trim((string) config('services.telegram-bot-api.token', ''));
        if ($botToken === '') {
            return $this->error('Telegram bot token is not configured', 422);
        }

        $baseUri = rtrim((string) config('services.telegram-bot-api.base_uri', 'https://api.telegram.org'), '/');
        $lastUpdateId = Cache::get('telegram:bot:last_update_id');
        $query = [
            'limit' => 50,
            'timeout' => 1,
        ];
        if (is_numeric($lastUpdateId)) {
            $query['offset'] = (int) $lastUpdateId + 1;
        }

        $response = Http::timeout(10)->get("{$baseUri}/bot{$botToken}/getUpdates", $query);
        if (!$response->successful()) {
            return $this->error('Failed to poll Telegram updates', 502);
        }

        $body = $response->json();
        if (!is_array($body) || empty($body['ok'])) {
            return $this->error('Invalid Telegram response', 502);
        }

        $updates = is_array($body['result'] ?? null) ? $body['result'] : [];
        $maxUpdateId = null;
        $linked = false;

        foreach ($updates as $update) {
            $updateId = $update['update_id'] ?? null;
            if (is_numeric($updateId)) {
                $maxUpdateId = $maxUpdateId === null ? (int) $updateId : max($maxUpdateId, (int) $updateId);
            }

            $message = $update['message'] ?? $update['edited_message'] ?? null;
            if (!is_array($message)) {
                continue;
            }

            $text = trim((string) ($message['text'] ?? ''));
            if ($text === '' || !str_starts_with($text, '/start')) {
                continue;
            }

            if (!preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9_-]{10,64})$/', $text, $matches)) {
                continue;
            }

            $token = $matches[1];
            $telegramUserId = (string) data_get($message, 'from.id', '');
            $telegramChatId = (string) data_get($message, 'chat.id', '');
            $telegramUsername = data_get($message, 'from.username');

            if ($telegramUserId === '' || $telegramChatId === '') {
                continue;
            }

            DB::transaction(function () use ($token, $customer, $telegramUserId, $telegramChatId, $telegramUsername, &$linked) {
                $linkToken = CustomerTelegramLinkToken::where('token', $token)
                    ->where('customer_id', $customer->id)
                    ->whereNull('consumed_at')
                    ->where('expires_at', '>', now())
                    ->lockForUpdate()
                    ->first();

                if (!$linkToken) {
                    return;
                }

                $alreadyLinked = Customer::where('telegram_user_id', $telegramUserId)
                    ->where('id', '!=', $customer->id)
                    ->exists();

                if ($alreadyLinked) {
                    return;
                }

                $customer->telegram_user_id = $telegramUserId;
                $customer->telegram_chat_id = $telegramChatId;
                $customer->telegram_username = $telegramUsername ? (string) $telegramUsername : null;
                $customer->enable_telegram_alerts = true;
                $customer->save();

                $linkToken->consumed_at = now();
                $linkToken->telegram_user_id = $telegramUserId;
                $linkToken->telegram_chat_id = $telegramChatId;
                $linkToken->telegram_username = $telegramUsername ? (string) $telegramUsername : null;
                $linkToken->save();

                $linked = true;
            });

            if ($linked) {
                break;
            }
        }

        if ($maxUpdateId !== null) {
            Cache::put('telegram:bot:last_update_id', $maxUpdateId, now()->addDay());
        }

        return $this->success(['linked' => $linked], $linked ? 'Telegram linked' : 'No link update found');
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
