<?php

namespace App\Http\Ussd\Saving\Services;

use App\Http\Ussd\ExitPrompt;
use App\Http\Ussd\Saving\ServicesScreen;
use Sparors\Ussd\State;

class EnterTransactionNumberScreen extends State
{
    protected function beforeRendering(): void
    {
        $savingAccount = $this->record->get('saving-account');

        $this->menu->text('CON ')->text('Enter Transaction No: ');
    }

    protected function afterRendering(string $transactionNumber): void
    {
        $this->record->set('transaction-number', $transactionNumber);

        $this->decision->any(CheckTransactionAction::class);
    }
}
