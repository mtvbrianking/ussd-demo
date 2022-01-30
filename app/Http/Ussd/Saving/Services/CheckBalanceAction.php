<?php

namespace App\Http\Ussd\Saving\Services;

use App\Models\User;
use App\Http\Ussd\CheckPin;
use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\Action;

class CheckBalanceAction extends Action
{
    public function run(): string
    {
        if(! $this->record->get('is-authorized', false)) {
            $this->record->set('auth-note', 'Authorize Check Bal. Enter PIN: ');

            $this->record->set('next-step', self::class);

            return CheckPin::class;
        }

        $savingAccount = $this->record->get('saving-account');

        $this->record->set('exit-note', 
            "Account: $savingAccount->number. Balance: {$savingAccount->formattedBalance}");

        return ExitPrompt::class;
    }
}
