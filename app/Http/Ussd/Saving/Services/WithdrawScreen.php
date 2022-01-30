<?php

namespace App\Http\Ussd\Saving\Services;

use App\Http\Ussd\ExitPrompt;
use App\Http\Ussd\Saving\ServicesScreen;
use Sparors\Ussd\State;

class WithdrawScreen extends State
{
    protected function beforeRendering(): void
    {
        $savingAccount = $this->record->get('saving-account');

        $this->menu
            ->text('CON ')
            ->line('Withdraw: ')
            ->listing([
                'To My Number',
                'To Another Number',
                'Back',
            ]);
    }

    protected function afterRendering(string $option): void
    {
        if($option == '1') {
            $phoneNumber = $this->record->get('phoneNumber');

            $this->record->set('recipient', $phoneNumber);
        }

        $this->decision
            ->equal('1', Withdraw\EnterAmountScreen::class)
            ->equal('2', Withdraw\EnterPhoneScreen::class)
            ->equal('3', ServicesScreen::class)
            ->any(ExitPrompt::class);
    }
}
