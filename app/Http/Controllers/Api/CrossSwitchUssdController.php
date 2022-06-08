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

class CrossSwitchUssdController extends Controller
{
    const SC = '*721#';

    public function __construct()
    {
        $this->middleware('log:api');
    }

    /**
     * @see https://docs.cross-switch.app/ussd-api-reference
     */
    public function __invoke(Request $request): JsonResponse
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

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->msisdn),
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
