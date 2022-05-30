<?php

namespace App\Ussd\Dto;

use Spatie\DataTransferObject\DataTransferObject;

// #[Strict]
class Item extends DataTransferObject
{
    public string|int $id;
    public string $label;
}
