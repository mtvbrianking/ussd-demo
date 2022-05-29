<?php

declare(strict_types=1);

namespace App\Ussd;

use Spatie\DataTransferObject\DataTransferObject;

class ListItem extends DataTransferObject
{
    public string|int $id;
    public string $label;
}
