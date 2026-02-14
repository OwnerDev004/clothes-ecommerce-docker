<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerOAuthAccount;
use App\Repositories\CustomerRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialRedirectController extends Controller
{
    protected CustomerRepository $customerRepository;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepository = $customerRepo;
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook', 'github'], true)) {
            return redirect($this->buildFrontendRedirect(null, 'unsupported_provider'));
        }

        $redirect = $this->sanitizeRedirect($request->query('redirect'));
        $driver = Socialite::driver($provider)->stateless();
        if ($redirect) {
            $driver = $driver->with(['state' => $this->encodeState(['redirect' => $redirect])]);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook', 'github'], true)) {
            return redirect($this->buildFrontendRedirect(null, 'unsupported_provider'));
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            return redirect($this->buildFrontendRedirect(null, 'oauth_failed'));
        }

        $customer = $this->findOrCreateCustomer($provider, $socialUser);
        $token = auth('customer')->login($customer);

        $redirect = $this->extractRedirectFromState($request->query('state'));
        return redirect($this->buildFrontendRedirect(null, null, $redirect, true))
            ->withCookie($this->buildAccessTokenCookie($token));
    }

    private function findOrCreateCustomer(string $provider, $socialUser)
    {
        $providerUserId = $socialUser->getId();
        $email = $socialUser->getEmail();
        $name = $socialUser->getName() ?: $socialUser->getNickname();
        $avatar = $socialUser->getAvatar();

        if (empty($providerUserId)) {
            abort(422, 'Provider user id missing');
        }

        $oauthAccount = CustomerOAuthAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($oauthAccount) {
            return $oauthAccount->customer;
        }

        $customer = $email ? $this->customerRepository->findByEmail($email) : null;
        if (!$customer) {
            $userName = $this->generateUniqueUsername($name ?: ($email ?: $providerUserId));
            $phone = $this->generatePlaceholderPhone($provider, $providerUserId);
            $customer = $this->customerRepository->create([
                'full_name' => $name,
                'user_name' => $userName,
                'email' => $email,
                'phone' => $phone,
                'password' => Str::random(32),
                'avatar_url' => $avatar,
            ]);
        } elseif (!$customer->email && $email) {
            $customer->email = $email;
            $customer->save();
        }

        CustomerOAuthAccount::create([
            'customer_id' => $customer->id,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email,
            'avatar_url' => $avatar,
        ]);

        return $customer;
    }

    private function generateUniqueUsername(string $seed): string
    {
        $base = Str::slug(Str::before($seed, '@'));
        if ($base === '') {
            $base = 'user';
        }
        $candidate = $base;
        $i = 0;
        while ($this->customerRepository->findByUsername($candidate)) {
            $i++;
            $candidate = $base . $i;
        }
        return $candidate;
    }

    private function generatePlaceholderPhone(string $provider, string $providerId): string
    {
        $hash = substr(hash('sha256', $provider . '|' . $providerId), 0, 16);
        $phone = 'oauth' . $hash;
        $i = 0;
        while ($this->customerRepository->findByPhone($phone)) {
            $i++;
            $phone = 'oauth' . substr(hash('sha256', $provider . '|' . $providerId . '|' . $i), 0, 16);
        }
        return $phone;
    }

    private function buildFrontendRedirect(?string $token, ?string $error = null, ?string $redirect = null, bool $success = false): string
    {
        $frontendBase = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
        $target = $redirect ?: $frontendBase . '/auth/login';

        $params = [];
        if ($token) {
            $params['token'] = $token;
        }
        if ($error) {
            $params['error'] = $error;
        }
        if ($success) {
            $params['success'] = 1;
        }

        if (!$params) {
            return $target;
        }

        $separator = str_contains($target, '?') ? '&' : '?';
        return $target . $separator . http_build_query($params);
    }

    private function buildAccessTokenCookie(string $token)
    {
        $minutes = (int) (auth('customer')->factory()->getTTL() ?: 60);
        $frontendHost = parse_url((string) config('app.frontend_url', 'http://localhost:3000'), PHP_URL_HOST);
        $cookieDomain = config('session.domain');

        if (!$cookieDomain && $frontendHost && !in_array($frontendHost, ['localhost', '127.0.0.1'], true)) {
            $cookieDomain = $frontendHost;
        }

        return cookie(
            'access_token',
            $token,
            $minutes,
            '/',
            $cookieDomain ?: null,
            app()->environment('production'),
            true,
            false,
            'lax'
        );
    }

    private function sanitizeRedirect(?string $redirect): ?string
    {
        if (!$redirect) {
            return null;
        }
        $frontendBase = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
        if (str_starts_with($redirect, $frontendBase)) {
            return $redirect;
        }
        return null;
    }

    private function encodeState(array $payload): string
    {
        return base64_encode(json_encode($payload));
    }

    private function extractRedirectFromState(?string $state): ?string
    {
        if (!$state) {
            return null;
        }
        $decoded = json_decode(base64_decode($state), true);
        if (!is_array($decoded) || empty($decoded['redirect'])) {
            return null;
        }
        return $this->sanitizeRedirect($decoded['redirect']);
    }
}
