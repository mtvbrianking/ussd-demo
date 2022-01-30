<?php

namespace App\Http\Ussd\Loan;

use Sparors\Ussd\State;

class AccountsScreen extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $this->menu->text('END ')->text('Coming soon...');
    }

    protected function afterRendering(string $argument): void
    {
        //
    }
}
