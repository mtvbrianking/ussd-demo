<?php

namespace App\Http\Ussd\Saving\Services\Deposit;

use App\Models\User;
use App\Http\Ussd\CheckPin;
use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\Action;

class DebitMsisdnAction extends Action
{
    public function run(): string
    {
        if(! $this->record->get('is-authorized', false)) {
            $this->record->set('auth-note', 'Enter SACCO PIN: ');

            $this->record->set('next-step', self::class);

            return CheckPin::class;
        }

        // Trigger debit towards the depositor's phone...
        // Send user an SMS when the TelCo confirms transfer

        $amount = $this->record->get('amount');
        $sender = $this->record->get('sender');

        $this->record->set('exit-note', "Approve debit of {$amount} on {$sender}.");

        return ExitPrompt::class;
    }
}
