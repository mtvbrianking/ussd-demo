<?php

namespace App\Http\Ussd\Loan;

use App\Models\User;
use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\Action;

class FetchAccountsAction extends Action
{
    public function run(): string
    {
        $user = $this->record->get('user');

        $loanAccounts = $user->loanAccounts;

        if($loanAccounts->count() == 0) {
            $this->record->set('exit-note', 'You have no loan accounts.');

            return ExitPrompt::class;
        }

        $this->record->set('loan-accounts', $loanAccounts);

        return AccountsScreen::class;
    }
}
