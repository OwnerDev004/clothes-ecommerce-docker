<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
// Customer Auth
Route::post("/login", [CustomerAuthController::class, 'login']);
Route::post("/register", [CustomerAuthController::class, 'register']);
