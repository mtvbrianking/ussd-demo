<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Ussd\CheckUserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
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

    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");

        Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", $this->incExp($exp));

        Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

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

    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $name = $attributes->getNamedItem("name")->nodeValue;
        $value = $attributes->getNamedItem("value")->nodeValue;

        Cache::put("{$this->cache_key}_{$name}", $value);

        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");

        Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", $this->incExp($exp));

        Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

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

    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");

        Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", $this->incExp($exp));

        Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

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
    protected string $cache_key;
    protected \DOMXPath $xpath;

    public function __construct($cache_key, ?\DOMXPath $xpath)
    {
        $this->cache_key = $cache_key;
        $this->xpath = $xpath;
    }

    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        $exp = preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);

        preg_match('/(\d+)(?!.*\d)/', $exp, $matches);

        return $matches[1] > 0 ? $exp : '';
    }

    protected function stepBack(string $exp, $limit = 1): string
    {
        $count = 0;

        // (\/option\[\d\]\/\*\[\d\])(?!.*[\/option\[\d\]\/\*\[\d\]])
        // (\/\*\[\d\])(?!.\*\[\d\])
        $exp = preg_replace_callback("|(\/\*\[\d\])(?!\*\[\d\])|", function($matches) { 
            return ''; 
        }, $exp, $limit, $count);

        if($count < $limit) {
            return '';
        }

        if(preg_match("|(\/\*\[\d\])$|", $exp) === 0) {
            return '';
        }

        return $exp;
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $header = $attributes->getNamedItem("header")->nodeValue;

        $body = '';

        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");

        Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $OptionEls = $this->xpath->query("{$exp}/option");
        
        foreach ($OptionEls as $idx => $OptionEl) {
            $pos = $idx + 1;
            $body .= "\n{$pos}) " . $OptionEl->attributes->getNamedItem("text")->nodeValue;
        }

        if(! $attributes->getNamedItem("noback")) {
            $body .= "\n0) Back";
        }

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", $this->incExp($exp));
        Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return "{$header}{$body}";
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        if($answer == '') {
            throw new \Exception("Invalid answer.");
        }

        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");

        Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        if($answer == 0) {
            if($attributes->getNamedItem("noback")) {
                throw new \Exception("Invalid option.");
            }

            $exp = $this->stepBack($pre, 2);
            $pre = $this->stepBack($pre, 3);

            Log::debug("SB       -->", ['pre' => $pre, 'exp' => $exp]);

            Cache::put("{$this->cache_key}_pre", $pre);
            Cache::put("{$this->cache_key}_exp", $exp);

            return;
        }

        if((int) $answer > $this->xpath->query("{$pre}/option")->length) {
            throw new \Exception("Invalid option.");
        }

        // Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", "{$pre}/*[{$answer}]");
        // Cache::put("{$this->cache_key}_exp", "{$pre}/*[{$answer}]/*[1]"); // into the option
        // Cache::put("{$this->cache_key}_exp", "{$pre}/option[{$answer}]/*[1]");
        Log::debug("CheckOut -->", ['pre' => $pre, 'exp' => "{$pre}/*[{$answer}]"]);

        // Cache::put("{$this->cache_key}_pre", $pre);
        // Cache::put("{$this->cache_key}_exp", "{$exp}/*[{$answer}]");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => "{$exp}/*[{$answer}]"]);
    }
}

class OptionTag
{
    protected string $cache_key;
    protected \DOMXPath $xpath;

    public function __construct($cache_key, ?\DOMXPath $xpath)
    {
        $this->cache_key = $cache_key;
        $this->xpath = $xpath;
    }

    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");
        $breakpoints = json_decode(Cache::get("{$this->cache_key}_breakpoints"), true);

        Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $no_of_tags = $this->xpath->query("{$exp}/*")->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);

        array_unshift($breakpoints, [$break => $this->incExp($pre)]);
        Cache::put("{$this->cache_key}_breakpoints", json_encode($breakpoints));

        Log::debug("BP       -->", ['break' => $break, 'resume' => $this->incExp($pre)]);

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", "{$exp}/*[1]");

        Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => "{$exp}/*[1]"]);

        return '';
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class IfTag
{
    protected string $cache_key;
    protected \DOMXPath $xpath;

    public function __construct($cache_key, ?\DOMXPath $xpath)
    {
        $this->cache_key = $cache_key;
        $this->xpath = $xpath;
    }

    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);
    }

    public function handle(\DOMNamedNodeMap $attributes) : ?string
    {
        $key = $attributes->getNamedItem("key")->nodeValue;
        $value = $attributes->getNamedItem("value")->nodeValue;

        if(Cache::get("{$this->cache_key}_{$key}") != $value) {
            $exp = Cache::get("{$this->cache_key}_exp");

            Cache::put("{$this->cache_key}_pre", $exp);
            Cache::put("{$this->cache_key}_exp", $this->incExp($exp));

            return '';
        }

        // Log::debug("----------------------------------------------------------------");

        $pre = Cache::get("{$this->cache_key}_pre");
        $exp = Cache::get("{$this->cache_key}_exp");
        // $break = Cache::get("{$this->cache_key}_break");
        $breakpoints = json_decode(Cache::get("{$this->cache_key}_breakpoints"), true);
        // $resume = Cache::get("{$this->cache_key}_resume");

        // Log::debug("- -->", ['pre' => $pre, 'exp' => $exp, 'break' => $break, 'resume' => $resume]);

        Cache::put("{$this->cache_key}_pre", $exp);
        Cache::put("{$this->cache_key}_exp", "{$exp}/*[1]");

        $no_of_tags = $this->xpath->query("{$exp}/*")->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);
        // $resume = $this->incExp($exp);
        array_unshift($breakpoints, [$break => $this->incExp($exp)]);
        // Cache::put("{$this->cache_key}_break", $break);
        Cache::put("{$this->cache_key}_breakpoints", json_encode($breakpoints));
        // Cache::put("{$this->cache_key}_resume", $resume);

        // Log::debug("<--- ", ['pre' => $exp, 'exp' => "{$exp}/*[1]", 'break' => $break, 'resume' => $resume]);

        return '';
    }

    public function process(\DOMNamedNodeMap $attributes, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
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

    public function walk($request, $xpath)
    {
        $cache_key = "{$request->phone_number}_{$request->service_code}";

        $pre = $this->cache->get("{$cache_key}_pre");

        if($pre) {
            $preNode = $xpath->query($pre)->item(0);

            Log::debug("Process  -->", ['tag' => $preNode->tagName, 'pre' => $pre]);

            if($preNode->tagName == 'question') {
                (new QuestionTag($cache_key))->process($preNode->attributes, $request->answer);
            } else if($preNode->tagName == 'options') {
                (new OptionsTag($cache_key, $xpath))->process($preNode->attributes, $request->answer);
            }
        }

        // ...........................................................................

        // // $pre = $this->cache->get("{$cache_key}_pre");
        // $exp = $this->cache->get("{$cache_key}_exp");
        // $break = $this->cache->get("{$cache_key}_break");
        // $resume = $this->cache->get("{$cache_key}_resume"); // ...
        // $breakpoints = json_decode($this->cache->get("{$cache_key}_breakpoints"), true);

        // // Log::debug("\nCheck", ['pre' => $pre, 'exp' => $exp, 'break' => $break, 'resume' => $resume, 'breakpoints' => $breakpoints]);

        // // Log::debug("{$exp} == {$break}");

        // if($breakpoints && isset($breakpoints[0][$exp])) {
        //     // Log::debug($exp, $breakpoints);
        //     $breakpoint = array_shift($breakpoints);
        //     $this->cache->put("{$cache_key}_exp", $breakpoint[$exp]);
        //     $this->cache->put("{$cache_key}_break", '');
        //     $this->cache->put("{$cache_key}_breakpoints", json_encode($breakpoints));
        //     $this->cache->put("{$cache_key}_resume", '');
        // }

        // ...........................................................................

        $exp = $this->cache->get("{$cache_key}_exp");

        $node = $xpath->query($exp)->item(0);

        if(! $node) {
            Log::debug("Error    -->", ['tag' => '', 'exp' => $exp]);

            // $pre = $this->cache->get("{$cache_key}_pre");
            $exp = $this->cache->get("{$cache_key}_exp");
            // $break = $this->cache->get("{$cache_key}_break");
            // $resume = $this->cache->get("{$cache_key}_resume"); // ...
            $breakpoints = json_decode($this->cache->get("{$cache_key}_breakpoints"), true);

            if(! $breakpoints || ! isset($breakpoints[0][$exp])) {
                throw new \Exception("Missing tag");
            }

            // Log::debug($exp, $breakpoints);
            $breakpoint = array_shift($breakpoints);
            $this->cache->put("{$cache_key}_exp", $breakpoint[$exp]);
            // $this->cache->put("{$cache_key}_break", '');
            $this->cache->put("{$cache_key}_breakpoints", json_encode($breakpoints));
            // $this->cache->put("{$cache_key}_resume", '');

            $exp = $this->cache->get("{$cache_key}_exp");

            $node = $xpath->query($exp)->item(0);
        }

        Log::debug("Handle   -->", ['tag' => $node->tagName, 'exp' => $exp]);

        if($node->tagName == 'variable') {
            $output = (new VariableTag($cache_key))->handle($node->attributes);
        } else if($node->tagName == 'question') {
            $output = (new QuestionTag($cache_key))->handle($node->attributes);
        } else if($node->tagName == 'response') {
            $output = (new ResponseTag($cache_key))->handle($node->attributes);
            throw new \Exception($output);
        } else if($node->tagName == 'options') {
            $output = (new OptionsTag($cache_key, $xpath))->handle($node->attributes);
        } else if($node->tagName == 'option') {
            $output = (new OptionTag($cache_key, $xpath))->handle($node->attributes);
        } else if($node->tagName == 'if') {
            $output = (new IfTag($cache_key, $xpath))->handle($node->attributes);
        } else {
            throw new \Exception("Unknown tag: {$node->tagName}");
        }

        // $this->cache->put("{$cache_key}_pre", $exp);
        // $this->cache->put("{$cache_key}_exp", $this->incExp($exp));

        // Log::debug("==== ", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        // $pre = $this->cache->get("{$cache_key}_pre");
        $exp = $this->cache->get("{$cache_key}_exp");
        // $break = $this->cache->get("{$cache_key}_break");
        // $resume = $this->cache->get("{$cache_key}_resume"); // ...
        $breakpoints = json_decode($this->cache->get("{$cache_key}_breakpoints"), true);

        // Log::debug("\nCheck", ['pre' => $pre, 'exp' => $exp, 'break' => $break, 'resume' => $resume, 'breakpoints' => $breakpoints]);

        // Log::debug("{$exp} == {$break}");

        if($breakpoints && isset($breakpoints[0][$exp])) {
            // Log::debug($exp, $breakpoints);
            $breakpoint = array_shift($breakpoints);
            $this->cache->put("{$cache_key}_exp", $breakpoint[$exp]);
            // $this->cache->put("{$cache_key}_break", '');
            $this->cache->put("{$cache_key}_breakpoints", json_encode($breakpoints));
            // $this->cache->put("{$cache_key}_resume", '');
        }

        // if($break && $exp == $break) {
        //     // $this->cache->put("{$cache_key}_pre", $pre);
        //     $resume = $this->cache->get("{$cache_key}_resume");

        //     $this->cache->put("{$cache_key}_exp", $resume);
        //     $this->cache->put("{$cache_key}_break", '');
        //     $this->cache->put("{$cache_key}_breakpoints", "[]");
        //     $this->cache->put("{$cache_key}_resume", '');
        // }

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
                'session_id' => $request->session_id,
            ]);
        }

        $doc->load(Storage::disk('local')->path('menus/customer.xml'));

        $xpath = new \DOMXPath($doc);

        // ...

        $cache_key = "{$request->phone_number}_{$request->service_code}";

        $preSessionId = $this->cache->get("{$cache_key}_session_id");

        // return response()->json(['flow' => self::FB, 'data' => "'{$preSessionId}' && '{$preSessionId}' != '{$request->session_id}'"]);

        // $data = DB::connection('sqlite_cache')->table('cache')->select(['key', 'value'])->where('key', 'like', "{$cache_key}_%")->get();

        // Log::debug('data', $data->toArray());

        if($preSessionId != $request->session_id) {
            if($preSessionId != '') {
                $affected = DB::connection('sqlite_cache')->table('cache')->where('key', 'like', "{$cache_key}_%")->delete();

                // return response()->json(['flow' => self::FB, 'data' => "{$cache_key}_% => {$affected}"]);
            }

            $this->cache->put("{$cache_key}_session_id", $request->session_id);

            $this->cache->put("{$cache_key}_pre", '');
            $this->cache->put("{$cache_key}_exp", "/menus/menu[@name='customer']/*[1]");
            // $this->cache->put("{$cache_key}_break", '');
            $this->cache->put("{$cache_key}_breakpoints", "[]");
            // $this->cache->put("{$cache_key}_resume", '');
        }

        // $sessionId = $this->cache->get("{$cache_key}_session_id");

        // return response()->json(['flow' => self::FB, 'data' => "'{$sessionId}'"]);

        // ...

        $session_id = $this->cache->get("{$cache_key}_session_id");

        try {
            $output = $this->walk($request, $xpath);
        } catch(\Exception $ex) {
            return response()->json([
                'flow' => self::FB, 
                'data' => $ex->getMessage(),
                'session_id' => $session_id,
            ]);
        }

        return response()->json([
            'flow' => self::FC, 
            'data' => $output,
            'session_id' => $session_id,
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
