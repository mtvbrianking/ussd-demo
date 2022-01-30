<?php

namespace App\Http\Ussd\Saving\Services\Deposit;

use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\State;

class EnterPhoneScreen extends State
{
    protected function beforeRendering(): void
    {
        $this->menu->text('CON ')->text('Enter Phone Number: ');
    }

    protected function afterRendering(string $phone): void
    {
        if(substr($phone, 0, 1) == '0') {
            $phone = substr_replace($phone, '256', 0, 1);
        }

        if(strlen($phone) != 12) {
            $this->record->set('exit-note', 'Invalid Phone Number.');

            $this->decision->any(ExitPrompt::class);
        }

        $networkCode = substr($phone, 3, 2);

        if(! in_array($networkCode, ['39', '76', '77', '78'])) {
            $this->record->set('exit-note', 'Currently supporting MTN UG.');

            $this->decision->any(ExitPrompt::class);
        }

        $this->record->set('sender', $phone);

        $this->decision->any(EnterAmountScreen::class);
    }
}
