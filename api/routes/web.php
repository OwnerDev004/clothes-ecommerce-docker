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



https://www.facebook.com/privacy/consent/?flow=gdp&params%5Bapp_id%5D=1630377581733106&params%5Blogger_id%5D=%2237895c4f-cc05-41ad-bb57-30ace8579b18%22&params%5Bnext%5D=%22confirm%22&params%5Bredirect_uri%5D=%22https%3A%5C%2F%5C%2Flocalhost%3A3000%5C%2Fauth%5C%2Flogin%22&params%5Bresponse_type%5D=%22code%22&params%5Bscope%5D=%5B%22email%22%5D&params%5Bsteps%5D=%7B%7D&params%5Bcui_gk%5D=%22%5BPASS%5D%3Aconfirm%22&source=gdp_delegated&cache_buster=-3705810276232516690