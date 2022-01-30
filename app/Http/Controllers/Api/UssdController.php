<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UssdController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return response($request->all());
    }
}