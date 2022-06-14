<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Parser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArkeselUssdController extends Controller
{
    const SC = '*721#';

    public function __construct()
    {
        $this->middleware('log:api');
    }

    /**
     * @see https://developers.arkesel.com API Documentation
     */
    public function __invoke(Request $request): JsonResponse
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
            $doc = new \DOMDocument();

            $doc->load(menus_path('menu.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, $request->sessionID))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->msisdn),
                ]);

            $input = $request->newSession ? '' : $request->userData;
            // $input = $this->getAnswer($request->newSession, $request->USERDATA, self::SC);

            $output = $parser->parse($input);
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

    protected function getAnswer($newSession, $userData, $serviceCode)
    {
        if(! $newSession) {
            return $userData;
        }

        if($serviceCode == $userData) {
            return '';
        }

        $serviceCode = trim(trim($serviceCode, '*'), '#');
        $userData = trim(trim($userData, '*'), '#');

        $userData = str_replace($serviceCode, '', $userData);

        return trim($userData, '*');
    }
}
