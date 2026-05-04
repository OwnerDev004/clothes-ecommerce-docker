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
use OpenApi\Attributes as OA;


class CustomerAuthController extends Controller
{
    use ApiResponse;
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepository = $customerRepo;

    }

    #[OA\Post(
        path: '/api/v1/auth/register',
        tags: ['Customer/Auth'],
        summary: 'Register customer',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['gender', 'email', 'user_name', 'phone', 'password'],
                properties: [
                    new OA\Property(property: 'full_name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'user_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Customer registered',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 409, description: 'Email or username exists'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/login',
        tags: ['Customer/Auth'],
        summary: 'Login customer',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_name', 'password'],
                properties: [
                    new OA\Property(property: 'user_name', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credentials invalid'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(CustomerLoginRequest $request)
    {
        $credentials = $request->only('user_name', 'password');
        $customer = $this->customerRepository->findByUsername($request->user_name);
        // Generate JWT token
        if ($customer && $customer->status !== 'active') {
            return $this->error('Customer account is inactive.', 403);
        }
        if (!$customer || !$token = auth('customer')->attempt($credentials)) {
            return $this->error('Credentials invalid', 401);
        }

        return $this->success(
            $this->buildAuthPayload($customer, $token),
            'Login successful'
        )->withCookie($this->buildAccessTokenCookie($token));
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot_password',
        tags: ['Customer/Auth'],
        summary: 'Send password reset link',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reset link status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Unable to send reset link'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function forgotPassword(CustomerForgotPasswordRequest $request)
    {
        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'If this email exists, a reset link has been sent.', 200);
        }

        return $this->error(__($status), 400);


    }



    #[OA\Post(
        path: '/api/v1/auth/reset_password',
        tags: ['Customer/Auth'],
        summary: 'Reset customer password',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                    new OA\Property(property: 'password_confirmation', type: 'string', minLength: 8),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Reset failed'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/oauth/{provider}',
        tags: ['Customer/Auth'],
        summary: 'OAuth login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['access_token'],
                properties: [
                    new OA\Property(property: 'access_token', type: 'string'),
                ]
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['google', 'facebook', 'github', 'telegram'])
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function oauthLogin(Request $request, string $provider)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['google', 'facebook', 'github', 'telegram'], true)) {
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

    #[OA\Post(
        path: '/api/v1/auth/oauth/cookie',
        tags: ['Customer/Auth'],
        summary: 'Store OAuth access token in cookie',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token stored'),
            new OA\Response(response: 401, description: 'Invalid token'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeAccessTokenCookie(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $guard = auth('customer')->setToken($data['token']);
            if (!$guard->check()) {
                return $this->error('Invalid token', 401);
            }
        } catch (\Throwable $e) {
            return $this->error('Invalid token', 401);
        }

        return $this->success(null, 'Token stored')
            ->withCookie($this->buildAccessTokenCookie($data['token']));
    }

    private function buildAuthPayload($customer, string $token): array
    {
        return [
            'customer' => $customer->makeHidden(['password', 'remember_token']),
            'requires_profile_completion' => $customer->requiresProfileOauthCompletion(),
            'requires_telegram_completion' => $customer->requiresTelegramAuthCompletion(),
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
