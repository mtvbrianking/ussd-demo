<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Ussd\CheckUserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Sparors\Ussd\Facades\Ussd;

class Parser
{
    protected \DOMXPath $xpath;

    protected string $exp;

    public function __construct(\DOMXPath $xpath, string $exp)
    {
        $this->xpath = $xpath;
        $this->exp = $exp;
    }

    protected function renderEl($el, $idx = null) {
        if($idx !== null) { ++$idx; echo "{$idx}) "; }
        
        echo "<{$el->tagName}";
        
        foreach ($el->attributes as $attr) { 
            echo " {$attr->name}=\"{$attr->value}\"";
        }

        echo "/>", PHP_EOL;
    }

    protected function readAnswers(&$answers = [])
    {
        $answer = array_shift($answers);

        if($answer !== null) {
            return $answer;
        }

        $stdin = fopen("php://stdin", "r");
        
        return trim(fgets($stdin));
    }

    public function walk($answers)
    {
        $els = $this->xpath->query($this->exp);

        foreach($els as $idx => $el) {
            $pos = ++$idx;
            
            $this->renderEl($el);

            if($el->tagName == 'options') {
                $OptionEls = $this->xpath->query("{$this->exp}[{$pos}]/option");
                foreach ($OptionEls as $idx => $OptionEl) {
                    $this->renderEl($OptionEl, $idx);
                }
            }

            $this->readAnswers($answers);
        }
    }
}

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';

    public function __construct()
    {
        $this->middleware('log:api');
    }
    
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $this->validate($request, [
            'session_id' => 'required|string',
            // 'network_code' => 'required|string',
            // 'phone_number' => 'required|string',
            // 'input' => 'nullable',
            // 'service_code' => 'required|string',
            // 'text' => 'nullable|string',
        ]);

        $exp = "/menus/menu[@name='customer']/*";

        if(! Cache::has("{$request->session_id}_exp")) {
            Cache::put("{$request->session_id}_exp", $exp);
        } else {
            $exp = Cache::get("{$request->session_id}_exp");
        }

        // ...

        $doc = new \DOMDocument();

        $doc->load(storage_path('menus/customer.xml'));

        $xpath = new \DOMXPath($doc);

        $parser = new Parser($xpath, $exp);

        $els = $xpath->query($exp);

        dd($els);

        // ...

        // $answers = []; // [1, 2, 1];

        // $parser->walk($answers);

        // return response()->json([
        //     'flow' => self::FC, 
        //     'data' => "Gender\n1) Male\n2) Female\n0) Back",
        // ]);

        return response()->json([
            'flow' => self::FB, 
            'data' => "You're request is being processed."
        ]);
    }

    public function old(Request $request): Response
    {
        $request['text'] = $this->lastInput($request->text);
        $request['phoneNumber'] = preg_replace('/[^0-9]/', '', $request->phoneNumber);

        $ussdMachine = Ussd::machine()
            ->setFromRequest([
                'sessionId' => 'sessionId',
                'phoneNumber' => 'phoneNumber',
                'network' => 'networkCode',
                'serviceCode' => 'serviceCode',
                'input' => 'text',
            ])
            ->setInitialState(CheckUserAction::class)
            ->setResponse(function (string $message, string $action) {
                return $message;
            });

        return response($ussdMachine->run());
    }

    protected function lastInput(?string $input): ?string
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
