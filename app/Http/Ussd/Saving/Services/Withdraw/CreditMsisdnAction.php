<?php

namespace App\Http\Ussd\Saving\Services\Withdraw;

use App\Models\User;
use App\Http\Ussd\CheckPin;
use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\Action;

class CreditMsisdnAction extends Action
{
    public function run(): string
    {
        if(! $this->record->get('is-authorized', false)) {
            $this->record->set('auth-note', 'Enter SACCO PIN: ');

            $this->record->set('next-step', self::class);

            return CheckPin::class;
        }

        // Initiate a transfer to the reciptient's number.
        // Send user an SMS when the TelCo confirms transfer

        $amount = $this->record->get('amount');
        $recipient = $this->record->get('recipient');

        $this->record->set('exit-note', "Initiated transfer of {$amount} to {$recipient}.");

        return ExitPrompt::class;
    }
}
