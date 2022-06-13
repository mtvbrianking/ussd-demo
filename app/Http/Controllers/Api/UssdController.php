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

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';
    const SC = '*721#';

    public function __construct()
    {
        $this->middleware('log:api');
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'network_code' => 'nullable|string',
            'phone_number' => 'required|string',
            // 'service_code' => 'required|string',
            'new_session' => 'required|in:yes,no',
            'input' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()
                ->json([
                    'flow' => self::FB,
                    'data' => 'The given data was invalid.',
                    // 'errors' => $validator->errors(),
                ]);
        }

        try {
            $doc = new \DOMDocument();

            $doc->load(menus_path('menus.xml'));

            $xpath = new \DOMXPath($doc);

            $parser = (new Parser($xpath, "/menus/menu[@name='sacco']/*[1]", $request->session_id))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->phone_number),
                ]);

            $input = $request->new_session == 'no' ? $request->input : '';
            // $input = $this->getAnswer($request->new_session, $request->input, self::SC);

            $output = $parser->parse($input);
        } catch(\Exception $ex) {
            return response()->json(['flow' => self::FB, 'data' => $ex->getMessage()]);
        }

        return response()->json(['flow' => self::FC, 'data' => $output]);
    }

    protected function getAnswer($new_session, $input, $service_code)
    {
        if($new_session == 'no') {
            return $input;
        }

        if($service_code == $input) {
            return '';
        }

        $service_code = trim(trim($service_code, '*'), '#');
        $input = trim(trim($input, '*'), '#');

        $input = str_replace($service_code, '', $input);

        return trim($input, '*');
    }
}
