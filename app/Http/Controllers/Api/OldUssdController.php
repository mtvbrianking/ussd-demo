<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Ussd\CheckUserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Sparors\Ussd\Facades\Ussd;

class OldUssdController extends Controller
{
    public function __construct()
    {
        // $this->middleware('log:api');
    }

    public function __invoke(Request $request): Response
    {
        $request['text'] = $this->lastInput($request->text);
        $request['phoneNumber'] = preg_replace('/[^0-9]/', '', $request->phoneNumber);

        $ussdMachine = Ussd::machine()
            ->setFromRequest([
                'sessionId' => 'sessionId',
                'phoneNumber' => 'phoneNumber',
                'network' => 'networkCode',
                'serviceCode' => 'serviceCode',
                'input' => 'text',
            ])
            ->setInitialState(CheckUserAction::class)
            ->setResponse(function (string $message, string $action) {
                return $message;
            });

        return response($ussdMachine->run());
    }

    protected function lastInput(?string $input) : ?string
    {
        if(! $input) {
            return $input;
        }

        if(! preg_match('/^[\d+\*]+[\d+]$/', $input)) {
            return $input;
        }

        $inputs = explode('*', $input);

        return end($inputs);
    }
}
