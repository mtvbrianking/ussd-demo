<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Ussd;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NaloUssdController extends Controller
{
    const SC = '*721#';

    public function __construct()
    {
        $this->middleware('log:api');
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
            $ussd = (new Ussd('menu.xml', $request->USERID))
                ->save([
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->MSISDN),
                ]);

            $message = $request->MSGTYPE == false ? $request->USERDATA : '';
            // $message = $this->getInput($request->MSGTYPE, $request->USERDATA, self::SC);

            $output = $ussd->handle($message);
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

    protected function getInput($MSGTYPE, $USERDATA, $SERVICECODE = '')
    {
        if($MSGTYPE == false) {
            return $USERDATA;
        }

        if(! $SERVICECODE) {
            return '';
        }

        if($SERVICECODE == $USERDATA) {
            return '';
        }

        $SERVICECODE = trim($SERVICECODE, '#');
        $USERDATA = trim($USERDATA, '#');

        $USERDATA = str_replace($SERVICECODE, '', $USERDATA);

        return trim($USERDATA, '*');
    }
}
