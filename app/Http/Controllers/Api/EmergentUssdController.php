<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Ussd;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmergentUssdController extends Controller
{
    public function __construct()
    {
        $this->middleware('log:api');
    }

    /**
     * @see https://simussd.interpayafrica.com USSD Simulator
     * @see https://www.scribd.com/document/533763762/Emergent-Technology-USSD-Gateway-API-V1-0-0-2
     */
    public function emergent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'SessionId' => 'required|string',
            'Mobile' => 'required|string',
            'ServiceCode' => 'nullable|string',
            'Type' => 'nullable|string', // in:Initiation,Response
            'Operator' => 'nullable|string',
            'Message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            // Log::error('errors', $validator->errors());

            return response()->json([
                'Message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
                'Type' => 'Release',
            ]);
        }

        try {
            $ussd = (new Ussd('menu.xml', $request->SessionId))
                ->save([
                    // 'service_code' => $request->ServiceCode,
                    // 'operator' => $request->Operator,
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
            'MaskNextRoute' => true,
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
