<?php
// Client
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;

// Admin
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;

//** Customers */ 

//Client  Auth
Route::prefix('auth')->group(function () {
    Route::post("/login", [CustomerAuthController::class, 'login']);
    Route::post("/register", [CustomerAuthController::class, 'register']);
    Route::post('/forgot_password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('/reset_password', [CustomerAuthController::class, 'resetPassword']);
    Route::post('/oauth/{provider}', [CustomerAuthController::class, 'oauthLogin']);
});




// Profile_Customer
Route::middleware(['auth:customer'])->group(function () {
    Route::get('/profile', [CustomerController::class, 'show']);
    Route::put('/profile', [CustomerController::class, 'update']);
    Route::post('/change_avatar', [CustomerController::class, 'editAvatar']);
    Route::post('/delete_avatar', [CustomerController::class, 'deleteAvatar']);
});

// Products
Route::prefix('product')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
});



//**Admin */ 

Route::prefix('admin')->group(function () {

    // Admin Auth
    Route::post("/login", [AdminAuthController::class, 'login']);
    Route::post("/register", [AdminAuthController::class, 'register']);

    // Products
    Route::prefix('product')->middleware(['auth:admin'])->group(function () {
        Route::get('/', [AdminProductController::class, 'index']);
        Route::post('/', [AdminProductController::class, 'store']);
        Route::put('/', [AdminProductController::class, 'update']);
        Route::delete('/', [AdminProductController::class, 'delete']);

    });


});

