<?php

namespace App\Http\Ussd\Saving;

use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\State;

class ServicesScreen extends State
{
    protected function beforeRendering(): void
    {
        $savingAccount = $this->record->get('saving-account');

        $this->menu
            ->text('CON ')
            ->line('Choose a savings service: ')
            ->listing([
                'Deposit',
                'Withdraw',
                'Balance',
                'Check Transaction',
                'Mini Statement',
                'Back',
            ]);
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision
            ->equal('1', Services\DepositScreen::class)
            ->equal('2', Services\WithdrawScreen::class)
            ->equal('3', Services\CheckBalanceAction::class)
            ->equal('4', Services\EnterTransactionNumberScreen::class)
            ->equal('5', Services\FetchMiniStatementAction::class)
            ->equal('6', AccountsScreen::class)
            ->any(ExitPrompt::class);
    }
}
