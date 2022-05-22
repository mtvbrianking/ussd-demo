<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Ussd\Parser;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';

    protected CacheContract $cache;

    public function __construct(CacheContract $cache)
    {
        $this->cache = $cache;
        // $this->middleware('log:api');
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

            if(Storage::disk('local')->missing('menus/customer.xml')) {
                throw new \Exception("Missing menu files.");
            }

            $doc->load(Storage::disk('local')->path('menus/customer.xml'));

            $xpath = new \DOMXPath($doc);

            $prefix = "{$request->phone_number}_{$request->service_code}";

            $exp = "/menus/menu[@name='customer']/*[1]";

            $parser = new Parser($this->cache, $prefix, $request->session_id, $exp, 120);

            $output = $parser->parse($xpath, $request->answer);
        } catch(\Exception $ex) {
            return response()
                ->json(['flow' => self::FB, 'data' => $ex->getMessage()])
                ->header('X-USSD-FLOW',self::FB);
        }

        return response()
            ->json(['flow' => self::FC, 'data' => $output])
            ->header('X-USSD-FLOW',self::FC);
    }
}
