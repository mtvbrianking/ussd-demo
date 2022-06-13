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

class KorbaUssdController extends Controller
{
    const SC = '*721#';

    public function __construct()
    {
        $this->middleware('log:api');
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

            $doc->load(menus_path('menus.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, "/menus/menu[@name='sacco']/*[1]", $request->sessionID))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->msisdn),
                ]);

            $message = $request->ussdServiceOp !== 1 ? $request->ussdString : '';
            // $message = $this->getInput($request->ussdServiceOp, $request->ussdString, self::SC);

            $output = $parser->parse($message);
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

    protected function getInput($ussdServiceOp, $ussdString, $serviceCode = '')
    {
        if($ussdServiceOp !== 1) {
            return $ussdString;
        }

        if(! $serviceCode) {
            return '';
        }

        if($serviceCode == $ussdString) {
            return '';
        }

        $serviceCode = trim($serviceCode, '#');
        $ussdString = trim($ussdString, '#');

        $ussdString = str_replace($serviceCode, '', $ussdString);

        return trim($ussdString, '*');
    }
}
