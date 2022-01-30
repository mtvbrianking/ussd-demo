<?php

namespace App\Http\Ussd\Saving\Services\Withdraw;

use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\State;

class EnterAmountScreen extends State
{
    protected function beforeRendering(): void
    {
        $this->menu->text('CON ')->text('Enter Amount: ');
    }

    protected function afterRendering(string $amount): void
    {
        $amount = intval($amount);

        if($amount < 1000) {
            $this->record->set('exit-note', 'Min: 1000');

            $this->decision->any(ExitPrompt::class);
        }

        $this->record->set('amount', $amount);

        $this->decision->any(CreditMsisdnAction::class);
    }
}
