<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Parser;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';

    protected CacheContract $cache;

    public function __construct(CacheContract $cache)
    {
        $this->cache = $cache;
        // $this->middleware('log:api');
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'network_code' => 'nullable|string',
            'phone_number' => 'required|string',
            'input' => 'nullable',
            'service_code' => 'required|string',
            'answer' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()
                ->json([
                    'flow' => self::FB,
                    'data' => 'The given data was invalid.',
                    // 'errors' => $validator->errors(),
                ])
                ->header('X-USSD-FLOW',self::FB);
        }

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                return response()->json(['flow' => self::FB, 'data' => 'Missing menu file.']);
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->session_id, $request->service_code))
                ->setOptions([
                    'phone_number' => $request->phone_number,
                ]);

            // $output = $parser->parse($request->answer);
            $output = $parser->parse($request->input);
        } catch(\Exception $ex) {
            return response()
                ->json(['flow' => self::FB, 'data' => $ex->getMessage()])
                ->header('X-USSD-FLOW',self::FB);
        }

        return response()
            ->json(['flow' => self::FC, 'data' => $output])
            ->header('X-USSD-FLOW',self::FC);
    }

    public function africastalking(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'networkCode' => 'nullable|string',
            'phoneNumber' => 'required|string',
            'serviceCode' => 'required|string',
            'text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            // $errors = json_encode($validator->errors());
            // return response("END The given data was invalid.\n{$errors}");
            return response('END The given data was invalid.');
        }

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                return response('END Missing menu file.');
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId, $request->serviceCode))
                ->setOptions([
                    'phone_number' => $request->phoneNumber,
                ]);

            $output = $parser->parse($request->text);

            // $answer = $this->lastInput($request->text);
            // $output = $parser->parse($answer);
        } catch(\Exception $ex) {
            return response("END " . $ex->getMessage());
        }

        return response("CON {$output}");
    }

    public function korba(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionID' => 'required|string',
            'network' => 'nullable|string',
            'msisdn' => 'required|string',
            // 'serviceCode' => 'nullable|string',
            'ussdString' => 'nullable|string',
            'ussdServiceOp' => 'nullable|int',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ussdServiceOp' => 1,
                'message' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
            ]);
        }

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $options = [
                'session_id' => $request->sessionID,
                'phone_number' => '256772100103', //preg_replace('/[^0-9]/', '', $request->msisdn),
                'service_code' => '*308#', // $request->serviceCode,
                'expression' => '/menu/*[1]',
            ];

            $parser = new Parser($xpath, $options, $this->cache, 120);

            $answer = $this->lastInput($request->ussdString);

            $output = $parser->parse($answer);
        } catch(\Exception $ex) {
            return response()->json([
                'ussdServiceOp' => 1,
                'message' => $ex->getMessage(),
            ]);
        }

        return response()->json([
            'ussdServiceOp' => 2,
            'message' => $output,
        ]);
    }

    public function nalo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'USERID' => 'required|string',
            'NETWORK' => 'nullable|string',
            'MSISDN' => 'required|string',
            // 'serviceCode' => 'nullable|string',
            'USERDATA' => 'nullable|string',
            'MSGTYPE' => 'nullable|bool',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'MSGTYPE' => false,
                'MSG' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
            ]);
        }

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $options = [
                'session_id' => $request->USERID,
                'phone_number' => '256772100103', //preg_replace('/[^0-9]/', '', $request->MSISDN),
                'service_code' => '*308#', // $request->serviceCode,
                'expression' => '/menu/*[1]',
            ];

            $parser = new Parser($xpath, $options, $this->cache, 120);

            $answer = $this->lastInput($request->USERDATA);

            $output = $parser->parse($answer);
        } catch(\Exception $ex) {
            return response()->json([
                'MSGTYPE' => false,
                'MSG' => $ex->getMessage(),
            ]);
        }

        return response()->json([
            'MSGTYPE' => true,
            'MSG' => $output,
        ]);
    }

    public function nsano(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'UserSessionID' => 'required|string',
            'network' => 'nullable|string',
            'msisdn' => 'required|string',
            // 'serviceCode' => 'nullable|string',
            'msg' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'USSDResp' => [
                    'action' => 'prompt',
                    'title' => 'The given data was invalid.',
                    'menus' => [],
                    // 'errors' => $validator->errors(),
                ]
            ]);
        }

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $options = [
                'session_id' => $request->UserSessionID,
                'phone_number' => '256772100103', //preg_replace('/[^0-9]/', '', $request->msisdn),
                'service_code' => '*308#', // $request->serviceCode,
                'expression' => '/menu/*[1]',
            ];

            $parser = new Parser($xpath, $options, $this->cache, 120);

            $answer = $this->lastInput($request->msg);

            $output = $parser->parse($answer);
        } catch(\Exception $ex) {
            return response()->json([
                'USSDResp' => [
                    'action' => 'prompt',
                    'title' => $ex->getMessage(),
                    'menus' => [],
                ],
            ]);
        }

        $menus = explode(PHP_EOL, $output);

        $title = array_shift($menus);

        return response()->json([
            'USSDResp' => [
                'action' => 'input',
                'title' => $title,
                'menus' => $menus,
            ],
        ]);
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

    protected function clean(string $code = ''): string
    {
        if(! $code) {
            return $code;
        }

        return rtrim(ltrim($code, '*'), '#');
    }

    protected function getAnswer(string $userInput = '', string $serviceCode = ''): string
    {
        if(! $userInput) {
            return '';
        }

        if(! $serviceCode) {
            return clean($userInput);
        }

        $userInput = clean($userInput);
        $serviceCode = clean($serviceCode);

        return clean(str_replace($serviceCode, '', $userInput));
    }
}
