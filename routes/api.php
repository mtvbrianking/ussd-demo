<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::post('/', [UssdController::class, '__invoke']);
