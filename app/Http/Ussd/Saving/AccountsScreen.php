<?php

namespace App\Http\Ussd\Saving;

use App\Http\Ussd\ExitPrompt;
use App\Http\Ussd\ServicesScreen as SaccoServicesScreen;
use Sparors\Ussd\State;

class AccountsScreen extends State
{
    protected function beforeRendering(): void
    {
        $accountNumbers = $this->record->get('saving-accounts')->pluck('number')->toArray();

        $options = array_merge($accountNumbers, ['Back']);

        $this->menu
            ->text('CON ')
            ->line('Choose Account: ')
            ->listing($options);
    }

    protected function afterRendering(string $argument): void
    {
        $argument = intval($argument);
        
        $savingAccounts = $this->record->get('saving-accounts');

        $selectedAccount = $savingAccounts->slice(--$argument, 1)->first();

        $this->record->set('saving-account', $selectedAccount);

        $noOfAccounts = $savingAccounts->count();

        $this->decision
            ->in(range(1, $noOfAccounts), ServicesScreen::class)
            ->equal(++$noOfAccounts, SaccoServicesScreen::class)
            ->any(ExitPrompt::class);
    }
}
