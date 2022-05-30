<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::any('/ussd/', [UssdController::class, '__invoke']);

Route::any('/ussd/at', [UssdController::class, 'africastalking']);
