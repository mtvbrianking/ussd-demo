<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::any('/', [UssdController::class, '__invoke']);

Route::post('/old', [UssdController::class, 'old']);
