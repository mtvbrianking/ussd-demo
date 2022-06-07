<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Parser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';

    public function __construct()
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
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                return response()->json(['flow' => self::FB, 'data' => 'Missing menu file.']);
            }

            $doc = new \DOMDocument();

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

    /**
     * @see https://developers.africastalking.com/docs/ussd/overview
     */
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
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                return response('END Missing menu file.');
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId, $request->serviceCode))
                ->setOptions([
                    'phone_number' => $request->phoneNumber,
                ]);

            $output = $parser->parse($request->text);
        } catch(\Exception $ex) {
            return response("END " . $ex->getMessage());
        }

        return response("CON {$output}");
    }

    /**
     * @see https://developers.arkesel.com API Documentation
     */
    public function arkesel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionID' => 'required|string',
            'network' => 'nullable|string',
            'msisdn' => 'required|string',
            'userData' => 'nullable|string',
            'newSession' => 'required|bool',
            'userID' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'sessionID' => $request->sessionID,
                'msisdn' => $request->msisdn,
                'userID' => $request->userID,
                'continueSession' => false,
                'message' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
            ]);
        }

        try {
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $serviceCode = $request->newSession ? $request->userData : '';

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionID, $serviceCode))
                ->setOptions([
                    'phone_number' => $request->msisdn,
                ]);

            $output = $parser->parse($request->userData);
        } catch(\Exception $ex) {
            return response()->json([
                'sessionID' => $request->sessionID,
                'msisdn' => $request->msisdn,
                'userID' => $request->userID,
                'continueSession' => false,
                'message' => $ex->getMessage(),
            ]);
        }

        return response()->json([
            'sessionID' => $request->sessionID,
            'msisdn' => $request->msisdn,
            'userID' => $request->userID,
            'continueSession' => true,
            'message' => $output,
        ]);
    }

    /**
     * @see https://simussd.interpayafrica.com USSD Simulator
     * @see https://www.scribd.com/document/533763762/Emergent-Technology-USSD-Gateway-API-V1-0-0-2
     */
    public function emergent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'Mobile' => 'required|string',
            'USERID' => 'nullable|string',
            'Type' => 'nullable|string',
            'Operator' => 'nullable|string',
            'Message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'Message' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
                'Type' => 'Release',
            ]);
        }

        try {
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId, $request->USERID))
                ->setOptions([
                    'phone_number' => $request->Mobile,
                ]);

            $output = $parser->parse($request->Message);
        } catch(\Exception $ex) {
            return response()->json([
                'Message' => $ex->getMessage(),
                'Type' => 'Release',
            ]);
        }

        return response()->json([
            'Message' => $output,
            'Type' => 'Response',
            'MaskNextRoute' => true,
        ]);
    }

    /**
     * @see https://developers.hubtel.com
     * @see https://github.com/hubtel/ussd-mocker
     * @see https://techmatters.me/2020-01-04-test-ussd-application-with-hubtel-ussd-gateway
     * @see http://ussdsimulator.herokuapp.com
     */
    public function hubtel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'SessionId' => 'required|string',
            'Mobile' => 'required|string',
            'ServiceCode' => 'nullable|string',
            'Type' => 'nullable|string',
            'Operator' => 'nullable|string',
            'Message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'Message' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
                'Type' => 'Release',
            ]);
        }

        try {
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->SessionId, $request->ServiceCode))
                ->setOptions([
                    'phone_number' => $request->Mobile,
                ]);

            $output = $parser->parse($request->Message);
        } catch(\Exception $ex) {
            return response()->json([
                'Message' => $ex->getMessage(),
                'Type' => 'Release',
            ]);
        }

        return response()->json([
            'Message' => $output,
            'Type' => 'Response',
        ]);
    }

    /**
     * @see https://ussdsimulator.pessewa.com
     * @see https://www.korba365.com
     */
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
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionID))
                ->setOptions([
                    'phone_number' => $request->msisdn,
                ]);

            $output = $parser->parse($request->ussdString);
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

    /**
     * @see https://ussdsimulator.pessewa.com
     * @see https://documenter.getpostman.com/view/7705958/UyrEhaLQ
     */
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
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->USERID))
                ->setOptions([
                    'phone_number' => $request->MSISDN,
                ]);

            $output = $parser->parse($request->USERDATA);
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

    /**
     * @see https://ussdsimulator.pessewa.com
     * @see https://www.nsano.com/pages/developers.html
     */
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
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->UserSessionID))
                ->setOptions([
                    'phone_number' => $request->msisdn,
                ]);

            $output = $parser->parse($request->msg);
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

    /**
     * @see https://ussd.southpawsl.com
     */
    public function southpawsl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'msisdn' => 'required|string',
            'ussdString' => 'nullable|string',
            // 'menuId' => 'nullable|string',
            // 'ussdParameters' => 'nullable|string',
            // 'network' => 'nullable|string',
            'text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
                'state' => 'END',
                'menuId' => $request->menuId,
                'ussdString' => $request->ussdString,
                'option' => '', // *
                'ussdParameter' => $request->ussdParameters,
            ]);
        }

        try {
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId, $request->ussdString))
                ->setOptions([
                    'phone_number' => $request->msisdn,
                ]);

            $output = $parser->parse($request->text);
        } catch(\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
                'state' => 'END',
                'menuId' => $request->menuId,
                'ussdString' => $request->ussdString,
                'option' => '', // *
                'ussdParameter' => $request->ussdParameters,
            ]);
        }

        return response()->json([
            'message' => $output,
            'state' => 'CONTINUE',
            'menuId' => $request->menuId,
            'ussdString' => $request->ussdString,
            'option' => '', // *
            'ussdParameter' => $request->ussdParameters,
        ]);
    }

    /**
     * @see https://docs.cross-switch.app/ussd-api-reference
     */
    public function crossSwitch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|string',
            'msisdn' => 'required|string',
            'ussdString' => 'nullable|string',
            // 'menuId' => 'nullable|string',
            // 'ussdParameters' => 'nullable|string',
            // 'network' => 'nullable|string',
            'text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                // 'errors' => $validator->errors(),
                'state' => 'END',
                'menuId' => $request->menuId,
                'ussdString' => $request->ussdString,
                'option' => '', // *
                'ussdParameter' => $request->ussdParameters,
            ]);
        }

        try {
            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc = new \DOMDocument();

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId, $request->ussdString))
                ->setOptions([
                    'phone_number' => $request->msisdn,
                ]);

            $output = $parser->parse($request->text);
        } catch(\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
                'state' => 'END',
                'menuId' => $request->menuId,
                'ussdString' => $request->ussdString,
                'option' => '', // *
                'ussdParameter' => $request->ussdParameters,
            ]);
        }

        return response()->json([
            'message' => $output,
            'state' => 'CONTINUE',
            'menuId' => $request->menuId,
            'ussdString' => $request->ussdString,
            'option' => '', // *
            'ussdParameter' => $request->ussdParameters,
        ]);
    }
}
