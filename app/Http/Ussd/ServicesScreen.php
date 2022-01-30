<?php

namespace App\Http\Ussd;

use Sparors\Ussd\State;

class ServicesScreen extends State
{
    protected function beforeRendering(): void
    {
        $this->menu
            ->text('CON ')
            ->line('DummySACCO')
            ->listing([
                'Savings',
                'Loans',
                'Exit',
            ]);
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision
            ->equal('1', Saving\FetchAccountsAction::class)
            ->equal('2', Loan\FetchAccountsAction::class)
            ->any(ExitPrompt::class);
    }
}
