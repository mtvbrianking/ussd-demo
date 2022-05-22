<?php

namespace App\Ussd\Tags;

use App\Ussd\Contracts\Tag;
use App\Ussd\Traits\ExpManipulators;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Log;

class IfTag implements Tag
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
        $key = $node->attributes->getNamedItem("key")->nodeValue;
        $value = $node->attributes->getNamedItem("value")->nodeValue;

        if($this->cache->get("{$this->prefix}_{$key}") != $value) {
            $exp = $this->cache->get("{$this->prefix}_exp");

            $this->cache->put("{$this->prefix}_pre", $exp, $this->ttl);
            $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), $this->ttl);

            return '';
        }

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = (array) json_decode((string) $this->cache->get("{$this->prefix}_breakpoints"), true);

        $this->cache->put("{$this->prefix}_pre", $exp, $this->ttl);
        $this->cache->put("{$this->prefix}_exp", "{$exp}/*[1]", $this->ttl);

        $no_of_tags = $this->xpath->query('*', $node)->length;
        $break = $this->incExp("{$exp}/*[1]", $no_of_tags);
        array_unshift($breakpoints, [$break => $this->incExp($exp)]);
        $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), $this->ttl);

        return '';
    }

    public function process(\DomNode $node, ?string $answer): void
    {
        return;
    }
}