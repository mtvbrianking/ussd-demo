<?php

namespace App\Http\Ussd\Saving;

use App\Models\User;
use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\Action;

class FetchAccountsAction extends Action
{
    public function run(): string
    {
        $user = $this->record->get('user');

        $savingAccounts = $user->savingAccounts;

        if($savingAccounts->count() == 0) {
            $this->record->set('exit-note', 'You have no saving accounts.');

            return ExitPrompt::class;
        }

        $this->record->set('saving-accounts', $savingAccounts);

        return AccountsScreen::class;
    }
}
