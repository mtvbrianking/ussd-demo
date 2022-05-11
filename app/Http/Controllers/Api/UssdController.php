<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Ussd\CheckUserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Sparors\Ussd\Facades\Ussd;

class ResponseTag
{
    protected $session_id;

    public function __construct($session_id)
    {
        $this->session_id = $session_id;
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        return $attributes->getNamedItem("text")->nodeValue;
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class VariableTag
{
    protected $session_id;

    public function __construct($session_id)
    {
        $this->session_id = $session_id;
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $name = $attributes->getNamedItem("name")->nodeValue;
        $value = $attributes->getNamedItem("value")->nodeValue;

        Cache::put("{$this->session_id}_{$name}", $value);

        return '';
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class QuestionTag
{
    protected $session_id;

    public function __construct($session_id)
    {
        $this->session_id = $session_id;
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        return $attributes->getNamedItem("text")->nodeValue;
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        if($answer == '') {
            throw new \Exception("Invalid answer.");
        }

        $name = $attributes->getNamedItem("name")->nodeValue;

        Cache::put("{$this->session_id}_{$name}", $answer);
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

    public function incExp(string $exp): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) { 
            return ++$matches[1]; 
        }, $exp);
    }

    public function decExp(string $exp): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) { 
            return --$matches[1]; 
        }, $exp);
    }

    public function walk($request, $xpath)
    {
        $pre = Cache::get("{$request->session_id}_pre");
        $exp = Cache::get("{$request->session_id}_exp");

        if($pre) {
            $preNode = $xpath->query($pre)->item(0);

            if($preNode->tagName == 'question') {
                (new QuestionTag($request->session_id))->process($preNode->attributes, $request->answer);
            }
        }

        $node = $xpath->query($exp)->item(0);

        if($node->tagName == 'variable') {
            $output = (new VariableTag($request->session_id))->handle($node->attributes);
        } else if($node->tagName == 'question') {
            $output = (new QuestionTag($request->session_id))->handle($node->attributes);
        } else if($node->tagName == 'response') {
            $output = (new ResponseTag($request->session_id))->handle($node->attributes);
            throw new \Exception($output);
        } else {
            throw new \Exception("Unknown tag: {$node->tagName}");
        }

        $pre = $exp;
        $exp = $this->incExp($exp);

        Cache::put("{$request->session_id}_pre", $pre);
        Cache::put("{$request->session_id}_exp", $exp);

        if(! $output) {
            return $this->walk($request, $xpath);
        }

        return $output;
    }
    
    public function __invoke(Request $request): JsonResponse
    {
        $this->validate($request, [
            'session_id' => 'required|string',
            // 'network_code' => 'required|string',
            // 'phone_number' => 'required|string',
            // 'input' => 'nullable',
            // 'service_code' => 'required|string',
            // 'text' => 'nullable|string',
        ]);

        // ...

        $doc = new \DOMDocument();

        $doc->load(storage_path('menus/customer.xml'));

        $xpath = new \DOMXPath($doc);

        // Exceptions 
        // - file not found (missing xml file)
        // - malformed xml (xmllinit)
        // - invalid xml (xsd validation)

        // ...

        $pre = '';
        $exp = "/menus/menu[@name='customer']/*[1]";

        if(! Cache::has("{$request->session_id}_exp")) {
            Cache::put("{$request->session_id}_pre", $pre);
            Cache::put("{$request->session_id}_exp", $exp);
        }

        try {
            $output = $this->walk($request, $xpath);
        } catch(\Exception $ex) {
            return response()->json([
                'flow' => self::FB, 
                'data' => $ex->getMessage(),
            ]);
        }

        return response()->json([
            'flow' => self::FC, 
            'data' => $output,
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
