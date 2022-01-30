<?php

namespace App\Http\Ussd\Saving\Services;

use App\Http\Ussd\ExitPrompt;
use App\Http\Ussd\Saving\ServicesScreen;
use Sparors\Ussd\State;

class DepositScreen extends State
{
    protected function beforeRendering(): void
    {
        $savingAccount = $this->record->get('saving-account');

        $this->menu
            ->text('CON ')
            ->line('Deposit: ')
            ->listing([
                'From My Number',
                'From Another Number',
                'Back',
            ]);
    }

    protected function afterRendering(string $option): void
    {
        if($option == '1') {
            $phoneNumber = $this->record->get('phoneNumber');

            $this->record->set('sender', $phoneNumber);
        }

        $this->decision
            ->equal('1', Deposit\EnterAmountScreen::class)
            ->equal('2', Deposit\EnterPhoneScreen::class)
            ->equal('3', ServicesScreen::class)
            ->any(ExitPrompt::class);
    }
}
