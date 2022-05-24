<?php

namespace App\Ussd\Tags;

use App\Ussd\Contracts\Tag;
use App\Ussd\Traits\ExpManipulators;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActionTag implements Tag
{
    use ExpManipulators;

    protected \DOMXPath $xpath;
    protected CacheContract $cache;
    protected string $prefix;
    protected int $ttl;

    public function __construct(\DOMXPath $xpath, CacheContract $cache, string $prefix, ?int $ttl = null)
    {
        $this->xpath = $xpath;
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    protected function createAction(string $fqcn, array $args = []): callable
    {
        if(! class_exists($fqcn)) {
            throw new \Exception("Missing class: {$fqcn}");
        }

        return call_user_func_array([new \ReflectionClass($fqcn), 'newInstance'], $args);
    }

    public function handle(\DomNode $node): ?string
    {
        $actionName = $node->attributes->getNamedItem("name")->nodeValue;

        $className = Str::studly($actionName);
        $action = $this->createAction("App\\Ussd\\Actions\\{$className}Action", [$this->cache, $this->prefix, $this->ttl]);
        $action($node);

        // throw new \Exception($this->cache->get("{$this->prefix}_amount"));

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $this->cache->put("{$this->prefix}_pre", $exp, $this->ttl);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), $this->ttl);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        return;
    }
}