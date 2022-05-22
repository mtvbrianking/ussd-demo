<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Ussd\CheckUserAction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Sparors\Ussd\Facades\Ussd;

trait ExpManipulators
{
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
}

interface Tag
{
    public function handle(\DomNode $node) : ?string;

    public function process(\DomNode $node, ?string $answer): void;
}

class ResponseTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return $node->attributes->getNamedItem("text")->nodeValue;
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class VariableTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $name = $node->attributes->getNamedItem("name")->nodeValue;
        $value = $node->attributes->getNamedItem("value")->nodeValue;

        $this->cache->put("{$this->prefix}_{$name}", $value, 120);

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class QuestionTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return $node->attributes->getNamedItem("text")->nodeValue;
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        if($answer == '') {
            throw new \Exception("Invalid answer.");
        }

        $name = $node->attributes->getNamedItem("name")->nodeValue;

        $this->cache->put("{$this->prefix}_{$name}", $answer, 120);
    }
}

class OptionsTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    protected function goBack(string $exp, int $steps = 1): string
    {
        $count = 0;

        $exp = preg_replace_callback("|(\/\*\[\d\]){{$steps}}$|", function($matches) { 
            return ''; 
        }, $exp, 1, $count);

        return $count === 1 ? $exp : '';
    }

    public function handle(\DomNode $node) : ?string
    {
        $header = $node->attributes->getNamedItem("header")->nodeValue;

        $body = '';

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $optionEls = $this->xpath->query("{$exp}/option");
        
        foreach ($optionEls as $idx => $optionEl) {
            $pos = $idx + 1;
            $body .= "\n{$pos}) " . $optionEl->attributes->getNamedItem("text")->nodeValue;
        }

        if(! $node->attributes->getNamedItem("noback")) {
            $body .= "\n0) Back";
        }

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), 120);
        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return "{$header}{$body}";
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        if($answer == '') {
            throw new \Exception("Invalid answer.");
        }

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        if($answer == 0) {
            if($node->attributes->getNamedItem("noback")) {
                throw new \Exception("Invalid choice.");
            }

            $exp = $this->goBack($pre, 2);

            // Log::debug("GoBack   -->", ['exp' => $exp]);

            $this->cache->put("{$this->prefix}_exp", $exp, 120);

            return;
        }

        if((int) $answer > $this->xpath->query("{$pre}/option")->length) {
            throw new \Exception("Invalid option.");
        }

        $this->cache->put("{$this->prefix}_exp", "{$pre}/*[{$answer}]", 120);
        // Log::debug("CheckOut -->", ['pre' => $pre, 'exp' => "{$pre}/*[{$answer}]"]);
    }
}

class OptionTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = json_decode($this->cache->get("{$this->prefix}_breakpoints"), true);

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $no_of_tags = $this->xpath->query("{$exp}/*")->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);

        array_unshift($breakpoints, [$break => $this->incExp($pre)]);
        $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), 120);

        // Log::debug("BP       -->", ['break' => $break, 'resume' => $this->incExp($pre)]);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", "{$exp}/*[1]", 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => "{$exp}/*[1]"]);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class IfTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $key = $node->attributes->getNamedItem("key")->nodeValue;
        $value = $node->attributes->getNamedItem("value")->nodeValue;

        if($this->cache->get("{$this->prefix}_{$key}") != $value) {
            $exp = $this->cache->get("{$this->prefix}_exp");

            $this->cache->put("{$this->prefix}_pre", $exp, 120);
            $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), 120);

            return '';
        }

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = json_decode($this->cache->get("{$this->prefix}_breakpoints"), true);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", "{$exp}/*[1]", 120);

        $no_of_tags = $this->xpath->query("{$exp}/*")->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);
        array_unshift($breakpoints, [$break => $this->incExp($exp)]);
        $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), 120);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class ChooseTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $whenEls = $this->xpath->query("{$exp}/when");

        $pos = 0;

        $isMatched = false;

        foreach ($whenEls as $idx => $whenEl) {
            $pos = $idx + 1;
            $key = $whenEl->attributes->getNamedItem("key")->nodeValue;
            $val = $whenEl->attributes->getNamedItem("value")->nodeValue;

            $var = $this->cache->get("{$this->prefix}_{$key}");

            if($var != $val) {
                continue;
            }

            $isMatched = true;

            $this->cache->put("{$this->prefix}_pre", $exp, 120);
            $this->cache->put("{$this->prefix}_exp", "{$exp}/*[{$pos}]", 120);

            break;
        }

        if($isMatched) {
            return '';
        }

        $otherwiseEl = $this->xpath->query("{$exp}/otherwise")->item(0);

        if(! $otherwiseEl) {
            return '';
        }

        ++$pos;

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", "{$exp}/*[{$pos}]", 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => "{$exp}/*[{$pos}]"]);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class WhenTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = json_decode($this->cache->get("{$this->prefix}_breakpoints"), true);

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $no_of_tags = $this->xpath->query("{$exp}/*")->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);

        array_unshift($breakpoints, [$break => $this->incExp($pre)]);
        $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), 120);

        // Log::debug("BP       -->", ['break' => $break, 'resume' => $this->incExp($pre)]);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", "{$exp}/*[1]", 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => "{$exp}/*[1]"]);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class OtherwiseTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = json_decode($this->cache->get("{$this->prefix}_breakpoints"), true);

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $no_of_tags = $this->xpath->query("{$exp}/*")->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);

        array_unshift($breakpoints, [$break => $this->incExp($pre)]);
        $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), 120);

        // Log::debug("BP       -->", ['break' => $break, 'resume' => $this->incExp($pre)]);

        $this->cache->put("{$this->prefix}_pre", $exp, 120);
        $this->cache->put("{$this->prefix}_exp", "{$exp}/*[1]", 120);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => "{$exp}/*[1]"]);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}

class Parser
{
    protected CacheContract $cache;
    protected string $prefix;

    public function __construct(CacheContract $cache, string $prefix)
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function parse(\DOMXPath $xpath, ?string $answer): string
    {
        // $prefix = "{$request->phone_number}_{$request->service_code}";

        $pre = $this->cache->get("{$this->prefix}_pre");

        if($pre) {
            $preNode = $xpath->query($pre)->item(0);

            // Log::debug("Process  -->", ['tag' => $preNode->tagName, 'pre' => $pre]);

            if($preNode->tagName == 'question') {
                (new QuestionTag($xpath, $this->cache, $this->prefix))->process($preNode, $answer);
            } else if($preNode->tagName == 'options') {
                (new OptionsTag($xpath, $this->cache, $this->prefix))->process($preNode, $answer);
            }
        }

        $exp = $this->cache->get("{$this->prefix}_exp");

        $node = $xpath->query($exp)->item(0);

        if(! $node) {
            // Log::debug("Error    -->", ['tag' => '', 'exp' => $exp]);

            $exp = $this->cache->get("{$this->prefix}_exp");
            $breakpoints = json_decode($this->cache->get("{$this->prefix}_breakpoints"), true);

            if(! $breakpoints || ! isset($breakpoints[0][$exp])) {
                throw new \Exception("Missing tag");
            }

            $breakpoint = array_shift($breakpoints);
            $this->cache->put("{$this->prefix}_exp", $breakpoint[$exp], 120);
            $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), 120);

            $exp = $this->cache->get("{$this->prefix}_exp");

            $node = $xpath->query($exp)->item(0);
        }

        // Log::debug("Handle   -->", ['tag' => $node->tagName, 'exp' => $exp]);

        if($node->tagName == 'variable') {
            $output = (new VariableTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'question') {
            $output = (new QuestionTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'response') {
            $output = (new ResponseTag($xpath, $this->cache, $this->prefix))->handle($node);
            throw new \Exception($output);
        } else if($node->tagName == 'options') {
            $output = (new OptionsTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'option') {
            $output = (new OptionTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'if') {
            $output = (new IfTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'choose') {
            $output = (new ChooseTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'when') {
            $output = (new WhenTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else if($node->tagName == 'otherwise') {
            $output = (new OtherwiseTag($xpath, $this->cache, $this->prefix))->handle($node);
        } else {
            throw new \Exception("Unknown tag: {$node->tagName}");
        }

        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = json_decode($this->cache->get("{$this->prefix}_breakpoints"), true);

        if($breakpoints && isset($breakpoints[0][$exp])) {
            $breakpoint = array_shift($breakpoints);
            $this->cache->put("{$this->prefix}_exp", $breakpoint[$exp], 120);
            $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), 120);
        }

        if(! $output) {
            return $this->parse($xpath, $answer);
        }

        return $output;
    }
}

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

        $prefix = "{$request->phone_number}_{$request->service_code}";

        $preSessionId = $this->cache->get("{$prefix}_session_id");

        if($preSessionId != $request->session_id) {
            if($preSessionId != '') {
                $affected = DB::connection('sqlite_cache')->table('cache')->where('key', 'like', "{$prefix}_%")->delete();
            }

            $this->cache->put("{$prefix}_session_id", $request->session_id, 120);

            $this->cache->put("{$prefix}_pre", '', 120);
            $this->cache->put("{$prefix}_exp", "/menus/menu[@name='customer']/*[1]", 120);
            $this->cache->put("{$prefix}_breakpoints", "[]", 120);
        }

        // ...

        $session_id = $this->cache->get("{$prefix}_session_id");

        try {
            $parser = new Parser($this->cache, $prefix);

            $output = $parser->parse($xpath, $request->answer);
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
