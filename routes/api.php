<?php

use App\Http\Controllers\Api\AfricasTalkingUssdController;
use App\Http\Controllers\Api\ArkeselUssdController;
use App\Http\Controllers\Api\EmergentUssdController;
use App\Http\Controllers\Api\CrossSwitchUssdController;
use App\Http\Controllers\Api\HubtelUssdController;
use App\Http\Controllers\Api\KorbaUssdController;
use App\Http\Controllers\Api\NaloUssdController;
use App\Http\Controllers\Api\NsanoUssdController;
use App\Http\Controllers\Api\SouthpawslUssdController;
use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ussd'], function () {
    Route::post('/', [UssdController::class, '__invoke']);

    Route::post('/africastalking', [AfricasTalkingUssdController::class, '__invoke']);

    Route::post('/arkesel', [ArkeselUssdController::class, '__invoke']);

    Route::post('/cross-switch', [CrossSwitchUssdController::class, '__invoke']);

    Route::post('/emergent', [EmergentUssdController::class, '__invoke']);

    Route::post('/hubtel', [HubtelUssdController::class, '__invoke']);

    Route::post('/korba', [KorbaUssdController::class, '__invoke']);

    Route::post('/nalo', [NaloUssdController::class, '__invoke']);

    Route::post('/nsano', [NsanoUssdController::class, '__invoke']);

    Route::post('/southpawsl', [SouthpawslUssdController::class, '__invoke']);
});
