<?php

namespace App\Ussd\Contracts;

interface Tag
{
    public function handle(\DomNode $node) : ?string;

    public function process(\DomNode $node, ?string $answer): void;
}
