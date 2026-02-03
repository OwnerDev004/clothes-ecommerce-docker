<?php

use Illuminate\Support\Facades\Route;

// API Version 1 Routes
Route::prefix("v1")->group(function(){base_path('routes/v1.php');});