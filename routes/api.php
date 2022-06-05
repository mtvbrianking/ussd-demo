<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

Route::::match(['get', 'post'], '/ussd', [UssdController::class, '__invoke']);

Route::::match(['get', 'post'], '/ussd/africastalking', [UssdController::class, 'africastalking']);

Route::::match(['get', 'post'], '/ussd/korba', [UssdController::class, 'korba']);

Route::::match(['get', 'post'], '/ussd/nalo', [UssdController::class, 'nalo']);

Route::::match(['get', 'post'], '/ussd/nsano', [UssdController::class, 'nsano']);
