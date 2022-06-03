<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Bmatovu\Ussd\Parser;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';

    protected CacheContract $cache;

    public function __construct(CacheContract $cache)
    {
        $this->cache = $cache;
        $this->middleware('log:api');
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->validate($request, [
            'session_id' => 'required|string',
            'network_code' => 'nullable|string',
            'phone_number' => 'required|string',
            'input' => 'nullable',
            'service_code' => 'required|string',
            'answer' => 'nullable|string',
        ]);

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $options = $request->only(['session_id', 'phone_number', 'service_code']);
            $options['expression'] = '/menu/*[1]';

            $parser = new Parser($xpath, $options, $this->cache, 120);

            $output = $parser->parse($request->answer);
        } catch(\Exception $ex) {
            return response()
                ->json(['flow' => self::FB, 'data' => $ex->getMessage()])
                ->header('X-USSD-FLOW',self::FB);
        }

        return response()
            ->json(['flow' => self::FC, 'data' => $output])
            ->header('X-USSD-FLOW',self::FC);
    }

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
            return response("END The given data was invalid.", 422);
        }

        try {
            $doc = new \DOMDocument();

            if(Storage::disk('local')->missing('ussd/sacco.xml')) {
                throw new \Exception("Missing menu file.");
            }

            $doc->load(Storage::disk('local')->path('ussd/sacco.xml'));

            $xpath = new \DOMXPath($doc);

            $options = [
                'session_id' => $request->sessionId,
                'phone_number' => preg_replace('/[^0-9]/', '', $request->phoneNumber),
                'service_code' => $request->serviceCode,
                'expression' => '/menu/*[1]',
            ];

            $parser = new Parser($xpath, $options, $this->cache, 120);

            $answer = $this->lastInput($request->text);

            $output = $parser->parse($answer);
        } catch(\Exception $ex) {
            return response("END " . $ex->getMessage());
        }

        return response("CON {$output}");
    }

    protected function lastInput(?string $input) : ?string
    {
        if(! $input) {
            return $input;
        }

        if(! preg_match('/^[\d+\*]+[\d+]$/', $input)) {
            return $input;
        }

        $inputs = explode('*', $input);

        return end($inputs);
    }
}
