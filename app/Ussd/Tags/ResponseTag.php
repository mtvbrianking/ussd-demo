<?php

namespace App\Ussd\Tags;

use App\Ussd\Contracts\Tag;
use App\Ussd\Traits\ExpManipulators;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Log;

class ResponseTag implements Tag
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

    public function handle(\DomNode $node) : ?string
    {
        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");

        // Log::debug("CheckIn  -->", ['pre' => $pre, 'exp' => $exp]);

        $this->cache->put("{$this->prefix}_pre", $exp, $this->ttl);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), $this->ttl);

        // Log::debug("CheckOut -->", ['pre' => $exp, 'exp' => $this->incExp($exp)]);

        return $node->attributes->getNamedItem("text")->nodeValue;
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        throw new \Exception("Expects no feedback.");
    }
}
