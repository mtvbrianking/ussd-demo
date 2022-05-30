<?php

namespace App\Ussd\Tags;

use App\Ussd\Dto\Item;
use Bmatovu\Ussd\Tags\BaseTag;
use Bmatovu\Ussd\Support\Helper;
use Illuminate\Container\Container;
use Illuminate\Support\Str;

class ListTag extends BaseTag
{
    public function handle(): ?string
    {
        $header = $this->node->attributes->getNamedItem('header')->nodeValue;

        $body = '';

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp", $this->node->getNodePath());

        $listAction = $this->node->attributes->getNamedItem('action')->nodeValue;
        $className = Str::studly($listAction);
        $action = $this->createAction("{$className}Action", [$this->cache, $this->prefix, $this->ttl]);
        $listItems = $action($this->node);

        $listName = $this->node->attributes->getNamedItem('name')->nodeValue;
        $this->cache->put("{$this->prefix}_{$listName}_list", $listItems, $this->ttl);

        $pos = 0;
        foreach ($listItems as $listItem) {
            ++$pos;
            $item = new Item($listItem);
            $body .= "\n{$pos}) ".$item->label;
        }

        if (! $this->node->attributes->getNamedItem('noback')) {
            $body .= "\n0) Back";
        }

        $this->cache->put("{$this->prefix}_pre", $exp, $this->ttl);
        $this->cache->put("{$this->prefix}_exp", $this->incExp($exp), $this->ttl);

        return "{$header}{$body}";
    }

    public function process(?string $answer): void
    {
        if ('' === $answer) {
            throw new \Exception('Make a choice.');
        }

        $pre = $this->cache->get("{$this->prefix}_pre");
        $exp = $this->cache->get("{$this->prefix}_exp", $this->node->getNodePath());

        if ('0' === $answer) {
            if ($this->node->attributes->getNamedItem('noback')) {
                throw new \Exception('Invalid choice.');
            }

            $exp = $this->goBack($pre, 2);

            $this->cache->put("{$this->prefix}_exp", $exp, $this->ttl);

            return;
        }

        $listName = $this->node->attributes->getNamedItem('name')->nodeValue;
        $listItems = $this->cache->pull("{$this->prefix}_{$listName}_list", []);

        if ((int) $answer > \count($listItems)) {
            throw new \Exception('Invalid choice.');
        }

        --$answer;

        $item = new Item($listItems[$answer]);

        $this->cache->put("{$this->prefix}_{$listName}_id", $item->id, $this->ttl);
        $this->cache->put("{$this->prefix}_{$listName}_label", $item->label, $this->ttl);

    }

    protected function goBack(string $exp, int $steps = 1): string
    {
        $count = 0;

        $exp = preg_replace_callback("|(\\/\\*\\[\\d\\]){{$steps}}$|", function ($matches) {
            return '';
        }, $exp, 1, $count);

        return 1 === $count ? $exp : '';
    }

    protected function resolveActionClass(string $actionName): string
    {
        $config = Container::getInstance()->make('config');

        $actionNs = config('ussd.action-ns');

        $fqcn = $actionName;

        foreach ($actionNs as $ns) {
            $fqcn = "{$ns}\\{$actionName}";
            if (class_exists($fqcn)) {
                return $fqcn;
            }
        }

        throw new \Exception("Missing class: {$actionName}");
    }

    protected function createAction(string $actionName, array $args = []): callable
    {
        $fqcn = $this->resolveActionClass($actionName);

        return \call_user_func_array([new \ReflectionClass($fqcn), 'newInstance'], $args);
    }
}
