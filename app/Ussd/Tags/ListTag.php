<?php

namespace App\Ussd\Tags;

use App\Ussd\Dto\Item;
use Bmatovu\Ussd\Contracts\AnswerableTag;
use Bmatovu\Ussd\Tags\BaseTag;
use Bmatovu\Ussd\Support\Helper;
use Illuminate\Container\Container;
use Illuminate\Support\Str;

class ListTag extends BaseTag implements AnswerableTag
{
    public function handle(): ?string
    {
        $header = $this->readAttr('header');

        $body = '';

        $pre = $this->fromCache("pre");
        $exp = $this->fromCache("exp", $this->node->getNodePath());

        $listAction = $this->readAttr('action');
        $className = Str::studly($listAction);
        $action = $this->createAction("{$className}Action", [$this->node, $this->cache, $this->prefix, $this->ttl]);
        $listItems = json_decode($action->handle(), true);

        $listName = $this->readAttr('name');
        $this->toCache("{$listName}_list", $listItems);

        $pos = 0;
        foreach ($listItems as $listItem) {
            ++$pos;
            $item = new Item($listItem);
            $body .= "\n{$pos}) ".$item->label;
        }

        if (! $this->readAttr('noback')) {
            $body .= "\n0) Back";
        }

        $this->toCache("pre", $exp);
        $this->toCache("exp", $this->incExp($exp));

        return "{$header}{$body}";
    }

    public function process(?string $answer): void
    {
        if ('' === $answer) {
            throw new \Exception('Make a choice.');
        }

        $pre = $this->fromCache("pre");
        $exp = $this->fromCache("exp", $this->node->getNodePath());

        if ('0' === $answer) {
            if ($this->readAttr('noback')) {
                throw new \Exception('Invalid choice.');
            }

            $exp = $this->goBack($pre, 2);

            $this->toCache("exp", $exp);

            return;
        }

        $listName = $this->readAttr('name');
        $listItems = $this->cache->pull("{$this->prefix}_{$listName}_list", []);

        if ((int) $answer > \count($listItems)) {
            throw new \Exception('Invalid choice.');
        }

        --$answer;

        $item = new Item($listItems[$answer]);

        $this->toCache("{$listName}_id", $item->id);
        $this->toCache("{$listName}_label", $item->label);

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

    protected function createAction(string $actionName, array $args = []): object
    {
        $fqcn = $this->resolveActionClass($actionName);

        return \call_user_func_array([new \ReflectionClass($fqcn), 'newInstance'], $args);
    }
}
