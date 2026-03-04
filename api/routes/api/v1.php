<?php
// Client
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\VoucherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\TelegramLinkController;
use App\Http\Controllers\Auth\SocialRedirectController;

// Admin
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Api\V1\Admin\VoucherController as AdminVoucherController;


//** Customers */ 

//Client  Auth
Route::prefix('auth')->group(function () {
    Route::post("/login", [CustomerAuthController::class, 'login']);
    Route::post("/register", [CustomerAuthController::class, 'register']);
    Route::post('/forgot_password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('/reset_password', [CustomerAuthController::class, 'resetPassword']);
    Route::post('/oauth/cookie', [CustomerAuthController::class, 'storeAccessTokenCookie']);
    Route::post('/oauth/{provider}', [CustomerAuthController::class, 'oauthLogin'])
        ->whereIn('provider', ['google', 'facebook', 'github', 'telegram']);
    Route::get('/telegram/redirect', [SocialRedirectController::class, 'redirect'])
        ->defaults('provider', 'telegram');
    Route::get('/telegram/callback', [SocialRedirectController::class, 'callback'])
        ->defaults('provider', 'telegram');
    Route::post('/telegram/cookie', [CustomerAuthController::class, 'storeAccessTokenCookie']);
});




// Profile_Customer
Route::middleware(['jwt.cookie', 'auth:customer'])->group(function () {
    Route::get('/profile', [CustomerController::class, 'show']);
    Route::put('/profile', [CustomerController::class, 'update']);
    Route::post('/change_avatar', [CustomerController::class, 'editAvatar']);
    Route::post('/delete_avatar', [CustomerController::class, 'deleteAvatar']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{variantId}', [CartController::class, 'updateItem']);
        Route::delete('/items/{variantId}', [CartController::class, 'removeItem']);
        Route::delete('/clear', [CartController::class, 'clear']);
    });

    Route::post('/checkout', [PaymentController::class, 'checkout']);
    Route::post('/payments/intent', [PaymentController::class, 'createIntent']);
    Route::get('/payments/khrqr/check/{hash}', [PaymentController::class, 'checkKhrqrStatus']);
    Route::post('/telegram/connect-link', [TelegramLinkController::class, 'createLink']);

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    });

    Route::prefix('vouchers')->group(function () {
        Route::post('validate', [VoucherController::class, 'validateVoucher']);
        Route::post('/apply', [VoucherController::class, 'applyVoucher']);
    });
});

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/filters', [ProductController::class, 'filters']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

Route::prefix('vouchers')->group(function () {
    Route::get('/', [VoucherController::class, 'index']);

});

Route::prefix('payments')->group(function () {
    Route::post('/webhook/{provider}', [PaymentController::class, 'webhook']);
});

Route::prefix('telegram')->group(function () {
    Route::post('/webhook/{secret}', [TelegramLinkController::class, 'webhook']);
});



//**Admin */ 

Route::prefix('admin')->group(function () {

    // Admin Auth
    Route::post("/login", [AdminAuthController::class, 'login']);
    Route::post("/register", [AdminAuthController::class, 'register']);

    // Products
    Route::prefix('products')->middleware(['auth:admin'])->group(function () {
        Route::get('/', [AdminProductController::class, 'index']);
        Route::get('/{id}', [AdminProductController::class, 'show']);
        Route::post('/', [AdminProductController::class, 'store']);
        Route::put('/{id}', [AdminProductController::class, 'update']);
        Route::delete('/{id}', [AdminProductController::class, 'destroy']);
    });

    // Products Variants
    Route::prefix('product_variants')->middleware(['auth:admin'])->group(function () {
        Route::get('/', [AdminProductVariantController::class, 'index']);
        Route::get('/{id}', [AdminProductVariantController::class, 'show']);
        Route::post('/', [AdminProductVariantController::class, 'store']);
        Route::put('/{id}', [AdminProductVariantController::class, 'update']);
        Route::delete('/{id}', [AdminProductVariantController::class, 'destroy']);
    });

    Route::prefix('orders')->middleware(['auth:admin'])->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/{id}/status', [AdminOrderController::class, 'updateStatus']);
    });

    Route::prefix('vouchers')->middleware(['auth:admin'])->group(function () {
        Route::get('/', [AdminVoucherController::class, 'index']);
        Route::get('/{id}', [AdminVoucherController::class, 'show']);
        Route::post('/', [AdminVoucherController::class, 'store']);
        Route::put('/{id}', [AdminVoucherController::class, 'update']);
        Route::delete('/{id}', [AdminVoucherController::class, 'destroy']);
    });


});
