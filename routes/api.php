<?php

use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Caster;
use Spatie\DataTransferObject\DataTransferObject;

Route::any('/ussd/', [UssdController::class, '__invoke']);

Route::any('/ussd/at', [UssdController::class, 'africastalking']);

#[Strict]
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
    // #[MapFrom(0)]
    #[CastWith(ItemArrayCaster::class)]
    public array $items;
}

Route::any('/', function() {
    $items = [
        ['id' => 1, 'label' => 'jdoe'],
        ['id' => '2', 'label' => 'bmatovu'],
    ];

    $list = new ListItems(items: $items);
    // $list = new ListItems(['items' => $items]);

    dd($list);
});
