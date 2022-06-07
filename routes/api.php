<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::post('/ussd', [UssdController::class, '__invoke']);

Route::post('/ussd/africastalking', [UssdController::class, 'africastalking']);

Route::post('/ussd/arkesel', [UssdController::class, 'arkesel']);

Route::post('/ussd/emergent', [UssdController::class, 'emergent']);

Route::post('/ussd/hubtel', [UssdController::class, 'hubtel']);

Route::post('/ussd/korba', [UssdController::class, 'korba']);

Route::post('/ussd/nalo', [UssdController::class, 'nalo']);

Route::post('/ussd/nsano', [UssdController::class, 'nsano']);

Route::post('/ussd/southpawsl', [UssdController::class, 'southpawsl']);

Route::post('/ussd/cross-switch', [UssdController::class, 'crossSwitch']);
