<?php

declare(strict_types=1);

namespace App\Ussd;

use Spatie\DataTransferObject\DataTransferObject;

class ListItem extends DataTransferObject
{
    public string|int $id;
    public string $label;
}

/*
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Caster;
use Spatie\DataTransferObject\DataTransferObject;

// #[Strict]
class Item extends DataTransferObject
{
    public string|int $id;
    public string $label;
}

class ItemArrayCaster implements Caster
{
    public function cast(mixed $values): array
    {
        if (! is_array($values)) {
            throw new \Exception("Can only cast arrays to Item");
        }

        return array_map(function($value) {
            return new Item($value);
        }, $values);
    }
}

class ListItems extends DataTransferObject
{
    #[CastWith(ItemArrayCaster::class)]
    public array $items;
}

$items = [
    ['id' => 1, 'label' => 'jdoe'],
    ['id' => '2', 'label' => 'bmatovu'],
];

// $list = new ListItems(['items' => $items]);
$list = new ListItems(items: $items);
*/
