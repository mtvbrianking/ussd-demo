<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Ussd;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HubtelUssdController extends Controller
{
    public function __construct()
    {
        $this->middleware('log:api');
    }

    /**
     * @see https://developers.hubtel.com
     * @see https://github.com/hubtel/ussd-mocker
     */
    public function hubtel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'SessionId' => 'required|string',
            'Mobile' => 'nullable|string',
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
            $ussd = (new Ussd('menu.xml', $request->SessionId))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->Mobile),
                ]);

            $message = $request->Type == 'Response' ? $request->Message : '';
            // $message = $this->getInput($request->Type, $request->Message, $request->ServiceCode);

            $output = $ussd->handle($message);
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

    protected function getInput($type, $message, $serviceCode)
    {
        if($type == 'Response') {
            return $message;
        }

        if($serviceCode == $message) {
            return '';
        }

        $serviceCode = trim($serviceCode, '#');
        $message = trim($message, '#');

        $message = str_replace($serviceCode, '', $message);

        return trim($message, '*');
    }
}
