<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Ussd\CheckUserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Sparors\Ussd\Facades\Ussd;

class ResponseTag
{
    protected $cache_key;

    public function __construct($cache_key)
    {
        $this->cache_key = $cache_key;
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
    protected $cache_key;

    public function __construct($cache_key)
    {
        $this->cache_key = $cache_key;
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $name = $attributes->getNamedItem("name")->nodeValue;
        $value = $attributes->getNamedItem("value")->nodeValue;

        Cache::put("{$this->cache_key}_{$name}", $value);

        return '';
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class QuestionTag
{
    protected $cache_key;

    public function __construct($cache_key)
    {
        $this->cache_key = $cache_key;
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

        Cache::put("{$this->cache_key}_{$name}", $answer);
    }
}

class OptionsTag
{
    // use DOMXPath;

    protected string $cache_key;
    protected \DOMXPath $xpath;

    public function __construct($cache_key, ?\DOMXPath $xpath)
    {
        $this->cache_key = $cache_key;
        $this->xpath = $xpath;
    }

    protected function stepBack(string $exp, $limit = 1): string
    {
        $count = 0;

        $exp = preg_replace_callback("|(\/option\[\d\]\/\*\[\d\])(?!.*[\/option\[\d\]\/\*\[\d\]])|", function($matches) { 
            return ''; 
        }, $exp, $limit, $count);

        if($count > 0) {
            return $exp;
        }

        preg_match('/(\d+)(?!.*\d)/', $exp, $matches);

        if($matches[1] < 2) { // last possible step
            return '';
        }

        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) { 
            return --$matches[1]; 
        }, $exp); 
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $header = $attributes->getNamedItem("header")->nodeValue;

        $body = '';

        $exp = Cache::get("{$this->cache_key}_exp");

        $OptionEls = $this->xpath->query("{$exp}/option");
        
        foreach ($OptionEls as $idx => $OptionEl) {
            $pos = $idx + 1;
            $body .= "\n{$pos}) " . $OptionEl->attributes->getNamedItem("text")->nodeValue;
        }

        if(! $attributes->getNamedItem("noback")) {
            $body .= "\n0) Back";
        }

        return "{$header}{$body}";
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        if($answer == '') {
            throw new \Exception("Invalid answer.");
        }

        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");

        if($answer == 0) {
            if($attributes->getNamedItem("noback")) {
                throw new \Exception("Invalid option.");
            }

            $exp = $this->stepBack($pre);
            $pre = $this->stepBack($exp);

            Cache::put("{$this->cache_key}_pre", $pre);
            Cache::put("{$this->cache_key}_exp", $exp);

            // Log::debug("\nPRE: {$pre}\nEXP: {$exp}");

            return;
        }

        if((int) $answer > $this->xpath->query("{$pre}/option")->length) {
            throw new \Exception("Invalid option.");
        }

        // Log::debug("\nPRE: {$pre}\nCUR: {$exp}\nNEXT: {$pre}/option[{$answer}]/*[1]");

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", "{$pre}/option[{$answer}]/*[1]");
    }
}

class UssdController extends Controller
{
    const FC = 'continue';
    const FB = 'break';

    protected CacheRepository $cache;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
        // $this->middleware('log:api');
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
        $cache_key = "{$request->phone_number}_{$request->service_code}";

        $pre = $this->cache->get("{$cache_key}_pre");

        if($pre) {
            $preNode = $xpath->query($pre)->item(0);

            if($preNode->tagName == 'question') {
                (new QuestionTag($cache_key))->process($preNode->attributes, $request->answer);
            } else if($preNode->tagName == 'options') {
                (new OptionsTag($cache_key, $xpath))->process($preNode->attributes, $request->answer);
            }
        }

        $exp = $this->cache->get("{$cache_key}_exp");

        // Log::debug("---> ", ['pre' => $pre, 'exp' => $exp]);

        $node = $xpath->query($exp)->item(0);

        if(! $node) {
            // there are no tags -- should have exited before this...
            throw new \Exception("Missing tag");    
        }

        if($node->tagName == 'variable') {
            $output = (new VariableTag($cache_key))->handle($node->attributes);
        } else if($node->tagName == 'question') {
            $output = (new QuestionTag($cache_key))->handle($node->attributes);
        } else if($node->tagName == 'response') {
            $output = (new ResponseTag($cache_key))->handle($node->attributes);
            throw new \Exception($output);
        } else if($node->tagName == 'options') {
            $output = (new OptionsTag($cache_key, $xpath))->handle($node->attributes);
        } else {
            throw new \Exception("Unknown tag: {$node->tagName}");
        }

        $this->cache->put("{$cache_key}_pre", $exp);
        $this->cache->put("{$cache_key}_exp", $this->incExp($exp));

        if(! $output) {
            return $this->walk($request, $xpath);
        }

        return $output;
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

        // ...

        $doc = new \DOMDocument();

        if(Storage::disk('local')->missing('menus/customer.xml')) {
            return response()->json([
                'flow' => self::FB, 
                'data' => "Missing menu files.",
            ]);
        }

        $doc->load(Storage::disk('local')->path('menus/customer.xml'));

        $xpath = new \DOMXPath($doc);

        // ...

        $cache_key = "{$request->phone_number}_{$request->service_code}";

        $preSessionId = $this->cache->get("{$cache_key}");

        if($preSessionId != $request->session_id) {
            // Flush previous session data
            // $this->cache->flush("{$cache_key}");

            $this->cache->put("{$cache_key}", $request->session_id);

            $this->cache->put("{$cache_key}_pre", '');
            $this->cache->put("{$cache_key}_exp", "/menus/menu[@name='customer']/*[1]");
        }

        // ...

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
