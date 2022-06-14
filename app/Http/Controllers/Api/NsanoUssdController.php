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

class NsanoUssdController extends Controller
{
    const SC = '*721#';

    public function __construct()
    {
        $this->middleware('log:api');
    }

    /**
     * @see https://ussdsimulator.pessewa.com
     * @see https://www.nsano.com/pages/developers.html
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'UserSessionID' => 'required|string',
            'network' => 'nullable|string',
            'msisdn' => 'required|string',
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

            $doc->load(menus_path('menu.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, $request->UserSessionID))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->msisdn),
                ]);

            $message = $this->getInput(self::SC, $request->msg);

            $output = $parser->parse($message);
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

    protected function getInput($serviceCode, $msg)
    {
        if(! Str::contains($msg, '*')) {
            return $msg;
        }

        $serviceCode = trim($serviceCode, '#');
        // $msg = trim($msg, '#');

        $msg = str_replace($serviceCode, '', $msg);

        return trim($msg, '*');
    }
}
