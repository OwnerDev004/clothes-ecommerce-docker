<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialRedirectController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth')->group(function () {
    Route::get('/{provider}/redirect', [SocialRedirectController::class, 'redirect'])
        ->name('auth.redirect');
    Route::get('/{provider}/callback', [SocialRedirectController::class, 'callback'])
        ->name('auth.callback');
});


