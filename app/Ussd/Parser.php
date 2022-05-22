<?php

namespace App\Ussd;

use App\Ussd\Contracts\Tag;
use App\Ussd\Tags\ChooseTag;
use App\Ussd\Tags\IfTag;
use App\Ussd\Tags\OptionsTag;
use App\Ussd\Tags\OptionTag;
use App\Ussd\Tags\OtherwiseTag;
use App\Ussd\Tags\QuestionTag;
use App\Ussd\Tags\ResponseTag;
use App\Ussd\Tags\VariableTag;
use App\Ussd\Tags\WhenTag;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Parser
{
    protected CacheContract $cache;
    protected string $prefix;
    protected int $ttl;

    public function __construct(CacheContract $cache, string $prefix, string $session_id, string $exp = "/*[1]", ?int $ttl = null)
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;

        $this->prepareCache($session_id, $exp);
    }

    protected function prepareCache(string $session_id, string $exp)
    {
        $preSessionId = $this->cache->get("{$this->prefix}_session_id");

        if($preSessionId == $session_id) {
            return;
        }

        if($preSessionId != '') {
            // $this->cache->tag($this->prefix)->flush();
        }

        $this->cache->put("{$this->prefix}_session_id", $session_id, $this->ttl);
        $this->cache->put("{$this->prefix}_pre", '', $this->ttl);
        $this->cache->put("{$this->prefix}_exp", $exp, $this->ttl);
        $this->cache->put("{$this->prefix}_breakpoints", "[]", $this->ttl);
    }

    protected function doProcess(\DOMXPath $xpath, ?string $answer): void
    {
        $pre = $this->cache->get("{$this->prefix}_pre");

        if(! $pre) {
            return;
        }

        $preNode = $xpath->query($pre)->item(0);

        // Log::debug("Process  -->", ['tag' => $preNode->tagName, 'pre' => $pre]);

        $tagName = Str::studly($preNode->tagName);
        $tag = $this->createTag(__NAMESPACE__."\\Tags\\{$tagName}Tag", [$xpath, $this->cache, $this->prefix, $this->ttl]);
        $tag->process($preNode, $answer);
    }

    protected function setBreakpoint(): void
    {
        $exp = $this->cache->get("{$this->prefix}_exp");

        $breakpoints = (array) json_decode((string) $this->cache->get("{$this->prefix}_breakpoints"), true);

        if(! $breakpoints || ! isset($breakpoints[0][$exp])) {
            throw new \Exception("Missing tag");
        }

        $breakpoint = array_shift($breakpoints);
        $this->cache->put("{$this->prefix}_exp", $breakpoint[$exp], $this->ttl);
        $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), $this->ttl);

        // return $this->cache->get("{$this->prefix}_exp");

        // return $xpath->query($exp)->item(0);
    }

    protected function doHandle(\DOMXPath $xpath): ?string
    {
        // Log::debug("Handle   -->", ['tag' => $node->tagName, 'exp' => $exp]);

        $exp = $this->cache->get("{$this->prefix}_exp");

        $node = $xpath->query($exp)->item(0);

        $tagName = Str::studly($node->tagName);
        $tag = $this->createTag(__NAMESPACE__."\\Tags\\{$tagName}Tag", [$xpath, $this->cache, $this->prefix, $this->ttl]);
        $output = $tag->handle($node);

        $exp = $this->cache->get("{$this->prefix}_exp");
        $breakpoints = (array) json_decode((string) $this->cache->get("{$this->prefix}_breakpoints"), true);

        if($breakpoints && isset($breakpoints[0][$exp])) {
            $breakpoint = array_shift($breakpoints);
            $this->cache->put("{$this->prefix}_exp", $breakpoint[$exp], $this->ttl);
            $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), $this->ttl);
        }

        return $output;
    }

    protected function createTag(string $fqcn, array $args = []): Tag
    {
        if(! class_exists($fqcn)) {
            throw new \Exception("Missing class: {$fqcn}");
        }

        return call_user_func_array([new \ReflectionClass($fqcn), 'newInstance'], $args);
    }

    public function parse(\DOMXPath $xpath, ?string $answer): string
    {
        // $pre = $this->cache->get("{$this->prefix}_pre");

        // if($pre) {
        //     $preNode = $xpath->query($pre)->item(0);

        //     // Log::debug("Process  -->", ['tag' => $preNode->tagName, 'pre' => $pre]);
            
        //     $tagName = Str::studly($preNode->tagName);
        //     $tag = $this->createTag(__NAMESPACE__."\\Tags\\{$tagName}Tag", [$xpath, $this->cache, $this->prefix, $this->ttl]);
        //     $tag->process($preNode, $answer);
        // }

        $this->doProcess($xpath, $answer);

        $exp = $this->cache->get("{$this->prefix}_exp");

        $node = $xpath->query($exp)->item(0);

        if(! $node) {
            // Log::debug("Error    -->", ['tag' => '', 'exp' => $exp]);

            // $exp = $this->cache->get("{$this->prefix}_exp");
            // $breakpoints = (array) json_decode((string) $this->cache->get("{$this->prefix}_breakpoints"), true);

            // if(! $breakpoints || ! isset($breakpoints[0][$exp])) {
            //     throw new \Exception("Missing tag");
            // }

            // $breakpoint = array_shift($breakpoints);
            // $this->cache->put("{$this->prefix}_exp", $breakpoint[$exp], $this->ttl);
            // $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), $this->ttl);

            // $exp = $this->cache->get("{$this->prefix}_exp");

            // $node = $xpath->query($exp)->item(0);

            $this->setBreakpoint();
        }

        // Log::debug("Handle   -->", ['tag' => $node->tagName, 'exp' => $exp]);

        // $tagName = Str::studly($node->tagName);
        // $tag = $this->createTag(__NAMESPACE__."\\Tags\\{$tagName}Tag", [$xpath, $this->cache, $this->prefix, $this->ttl]);
        // $output = $tag->handle($node);

        // $exp = $this->cache->get("{$this->prefix}_exp");
        // $breakpoints = (array) json_decode((string) $this->cache->get("{$this->prefix}_breakpoints"), true);

        // if($breakpoints && isset($breakpoints[0][$exp])) {
        //     $breakpoint = array_shift($breakpoints);
        //     $this->cache->put("{$this->prefix}_exp", $breakpoint[$exp], $this->ttl);
        //     $this->cache->put("{$this->prefix}_breakpoints", json_encode($breakpoints), $this->ttl);
        // }

        $output = $this->doHandle($xpath);

        if(! $output) {
            return $this->parse($xpath, $answer);
        }

        return $output;
    }
}
