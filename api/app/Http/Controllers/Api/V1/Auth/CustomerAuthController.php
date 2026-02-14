<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CustomerForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\CustomerLoginRequest;
use App\Http\Requests\Api\V1\Auth\CustomerRegisterRequest;
use App\Http\Requests\Api\V1\Auth\CustomerResetPasswordRequest;
use App\Models\CustomerOAuthAccount;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Laravel\Socialite\Facades\Socialite;


class CustomerAuthController extends Controller
{
    use ApiResponse;
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepository = $customerRepo;

    }

    public function register(CustomerRegisterRequest $request)
    {
        if ($this->customerRepository->findByEmail($request->email)) {
            return $this->error("Email Exist Please Try aggain", 409);
        }
        if ($this->customerRepository->findByUsername($request->user_name)) {
            return $this->error("UserName Exist Please Try aggain", 409);
        }

        $customer = $this->customerRepository->create($request->validated());
        $token = auth('customer')->login($customer);

        return $this->success(
            $this->buildAuthPayload($customer, $token),
            'Customer registered successfully',
            201
        )->withCookie($this->buildAccessTokenCookie($token));
    }

    public function login(CustomerLoginRequest $request)
    {
        $credentials = $request->only('user_name', 'password');
        $customer = $this->customerRepository->findByUsername($request->user_name);
        // Generate JWT token
        if (!$customer || !$token = auth('customer')->attempt($credentials)) {
            return $this->error('Credentials invalid', 401);
        }

        return $this->success(
            $this->buildAuthPayload($customer, $token),
            'Login successful'
        )->withCookie($this->buildAccessTokenCookie($token));
    }

    public function forgotPassword(CustomerForgotPasswordRequest $request)
    {
        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'If this email exists, a reset link has been sent.', 200);
        }

        return $this->error(__($status), 400);


    }



    public function resetPassword(CustomerResetPasswordRequest $request)
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer, $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, __($status), 200);
        }

        return $this->error(__($status), 400);
    }

    public function oauthLogin(Request $request, string $provider)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook', 'github'], true)) {
            return $this->error('Unsupported provider', 422);
        }

        $data = $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($data['access_token']);
        } catch (\Throwable $e) {
            return $this->error(ucfirst($provider) . ' token invalid', 401);
        }

        $providerUserId = $socialUser->getId();
        $email = $socialUser->getEmail();
        $name = $socialUser->getName() ?: $socialUser->getNickname();
        $avatar = $socialUser->getAvatar();

        if (empty($providerUserId)) {
            return $this->error('Provider user id missing', 422);
        }

        $oauthAccount = CustomerOAuthAccount::where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($oauthAccount) {
            $customer = $oauthAccount->customer;
        } else {
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

            $oauthAccount = CustomerOAuthAccount::create([
                'customer_id' => $customer->id,
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
                'email' => $email,
                'avatar_url' => $avatar,
            ]);
        }

        $token = auth('customer')->login($customer);

        return $this->success(
            $this->buildAuthPayload($customer, $token),
            'Login successful'
        )->withCookie($this->buildAccessTokenCookie($token));
    }

    private function buildAuthPayload($customer, string $token): array
    {
        return [
            'customer' => $customer->makeHidden(['password', 'remember_token']),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('customer')->factory()->getTTL() * 60,
        ];
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
}
