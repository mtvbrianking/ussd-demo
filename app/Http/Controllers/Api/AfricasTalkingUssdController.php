<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Parser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AfricasTalkingUssdController extends Controller
{
    public function __construct()
    {
        $this->middleware('log:api');
    }

    /**
     * @see https://developers.africastalking.com/docs/ussd/overview
     */
    public function __invoke(Request $request): Response
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

            $parser = (new Parser($xpath, '/menu/*[1]', $request->sessionId))
                ->save([
                    // 'service_code' => $request->serviceCode,
                    // 'network_code' => $request->networkCode,
                    'phone_number' => preg_replace('/[^0-9]/', '', $request->phoneNumber),
                ]);

            $output = $parser->parse($request->text);
        } catch(\Exception $ex) {
            return response("END " . $ex->getMessage());
        }

        return response("CON {$output}");
    }
}
