<?php
// Client
use App\Http\Controllers\Api\V1\NewsLetterSubScribeController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\SubCategoryController;
use App\Http\Controllers\Api\V1\VoucherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\TelegramLinkController;
use App\Http\Controllers\Auth\SocialRedirectController;

// Admin
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Api\V1\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Api\V1\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Api\V1\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\StockPurchaseController as AdminStockPurchaseController;
use Illuminate\Support\Facades\Broadcast;


//** Customers */ 

Broadcast::routes([
    'middleware' => [
        'jwt.cookie',
        \App\Http\Middleware\AuthenticateBroadcastToken::class,
    ],
]);

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

    // Add route for Telegram alerts
    Route::post('/telegram/alerts', [CustomerController::class, 'updateTelegramAlerts']);

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
    Route::post('/payments/khrqr/cancel', [PaymentController::class, 'cancelKhrqrIntent']);
    Route::post('/payments/simulate-paid', [PaymentController::class, 'simulatePaid']);
    Route::post('/telegram/connect-link', [TelegramLinkController::class, 'createLink']);
    Route::post('/telegram/poll-link', [TelegramLinkController::class, 'pollLink']);

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    });

    Route::prefix('vouchers')->group(function () {
        Route::post('validate', [VoucherController::class, 'validateVoucher']);
        Route::post('/apply', [VoucherController::class, 'applyVoucher']);
    });

    Route::prefix('products')->group(function () {
        Route::post('/{id}/reviews', [ProductController::class, 'storeReview']);
    });
});

// Products
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/filters', [ProductController::class, 'filters']);
    Route::get('/{id}/detail-sections', [ProductController::class, 'detailSections']);
    Route::get('/{id}/reviews', [ProductController::class, 'reviewByProduct']);
    Route::get('/top_review', [ProductController::class, 'topFiveReviews']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

Route::prefix('brands')->group(function () {
    Route::get('/', [BrandController::class, 'index']);
});

Route::prefix('collections')->group(function () {
    Route::get('/', [CollectionController::class, 'index']);
    Route::get('/{collection:slug}', [CollectionController::class, 'show']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category:slug}', [CategoryController::class, 'show']);
});

Route::prefix('sub-categories')->group(function () {
    Route::get('/', [SubCategoryController::class, 'index']);
});

Route::prefix('vouchers')->group(function () {
    Route::get('/', [VoucherController::class, 'index']);
    Route::get('/signup-offer', [VoucherController::class, 'signupOffer']);

});

Route::prefix('payments')->group(function () {
    Route::post('/webhook/{provider}', [PaymentController::class, 'webhook']);
});

Route::prefix('telegram')->group(function () {
    Route::post('/webhook/{secret}', [TelegramLinkController::class, 'webhook']);
});

// newsletters
Route::post('newsletters/subscribe', [NewsLetterSubScribeController::class, 'subscribeMail']);





//**Admin */ 

Route::prefix('admin')->group(function () {

    // Admin Auth
    Route::post("/login", [AdminAuthController::class, 'login']);
    Route::post("/register", [AdminAuthController::class, 'register']);
    Route::middleware(['auth:admin'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/profile', [AdminAuthController::class, 'show']);
        });

        // dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index']);
        });

        // analytics
        Route::prefix('analytics')->group(function () {
            Route::get('/', [AdminAnalyticsController::class, 'index']);
        });

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminProductController::class, 'index']);
            Route::get('/{id}', [AdminProductController::class, 'show']);
            Route::post('/', [AdminProductController::class, 'store']);
            Route::put('/{id}', [AdminProductController::class, 'update']);
            Route::delete('/{id}', [AdminProductController::class, 'destroy']);
        });

        // Products Variants
        Route::prefix('product_variants')->group(function () {
            Route::get('/', [AdminProductVariantController::class, 'index']);
            Route::get('/{id}', [AdminProductVariantController::class, 'show']);
            Route::post('/', [AdminProductVariantController::class, 'store']);
            Route::put('/{id}', [AdminProductVariantController::class, 'update']);
            Route::delete('/{id}', [AdminProductVariantController::class, 'destroy']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index']);
            Route::get('/{id}', [AdminOrderController::class, 'show']);
            Route::patch('/{id}', [AdminOrderController::class, 'updateOrder']);
            Route::patch('/{id}/status', [AdminOrderController::class, 'updateStatus']);

        });

        Route::prefix('purchases')->group(function () {
            Route::get('/', [AdminStockPurchaseController::class, 'index']);
            Route::post('/', [AdminStockPurchaseController::class, 'store']);
            Route::put('/{id}', [AdminStockPurchaseController::class, 'update']);
            Route::delete('/{id}', [AdminStockPurchaseController::class, 'destroy']);
        });

        Route::prefix('vouchers')->group(function () {
            Route::get('/', [AdminVoucherController::class, 'index']);
            Route::get('/{id}', [AdminVoucherController::class, 'show']);
            Route::post('/', [AdminVoucherController::class, 'store']);
            Route::put('/{id}', [AdminVoucherController::class, 'update']);
            Route::delete('/{id}', [AdminVoucherController::class, 'destroy']);
        });

        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index']);
            Route::get('/{category:id}', [AdminCategoryController::class, 'show']);
            Route::post('/', [AdminCategoryController::class, 'store']);
            Route::put('/{category:id}', [AdminCategoryController::class, 'update']);
            Route::delete('/{category:id}', [AdminCategoryController::class, 'destroy']);
        });
        Route::prefix('customers')->group(function () {
            Route::get('/', [AdminCustomerController::class, 'index']);
            Route::get('/{customer:id}', [AdminCustomerController::class, 'show']);
            Route::post('/{customer:id}/send-reset-link', [AdminCustomerController::class, 'sendResetLink']);
            Route::put('/{customer:id}', [AdminCustomerController::class, 'update']);
            Route::delete('/{customer:id}', [AdminCustomerController::class, 'destroy']);
        });

        Route::prefix('brands')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index']);
            Route::get('/{brand}', [AdminBrandController::class, 'show']);
            Route::post('/', [AdminBrandController::class, 'store']);
            Route::put('/{brand}', [AdminBrandController::class, 'update']);
            Route::delete('/{brand}', [AdminBrandController::class, 'destroy']);
        });

        Route::prefix('collections')->group(function () {
            Route::get('/', [AdminCollectionController::class, 'index']);
            Route::get('/{collection}', [AdminCollectionController::class, 'show']);
            Route::post('/', [AdminCollectionController::class, 'store']);
            Route::put('/{collection}', [AdminCollectionController::class, 'update']);
            Route::delete('/{collection}', [AdminCollectionController::class, 'destroy']);
        });
    });


});
