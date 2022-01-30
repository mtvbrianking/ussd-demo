<?php

namespace App\Http\Ussd\Saving\Services;

use App\Models\User;
use App\Http\Ussd\CheckPin;
use App\Http\Ussd\ExitPrompt;
use App\Http\Ussd\Saving\AccountsScreen as SavingAccountsScreen;
use Sparors\Ussd\Action;

class FetchMiniStatementAction extends Action
{
    public function run(): string
    {
        if(! $this->record->get('is-authorized', false)) {
            $this->record->set('auth-note', 'Authorize Check Mini-Statement. Enter PIN: ');

            $this->record->set('next-step', self::class);

            return CheckPin::class;
        }

        $savingAccount = $this->record->get('saving-account');

        $transactions = $savingAccount->recentTransactions()->take(5)->get();

        if($transactions->count() == 0) {
            $this->record->set('exit-note', "No transactions...");

            return ExitPrompt::class;
        }

        $miniStatement = '';

        foreach($transactions as $transaction) {
            $stmt = [
                '#' . $transaction->id,
                ucfirst($transaction->type),
                $transaction->formattedAmount,
                $transaction->created_at->format('d-m-Y H:i'),
            ];

            $miniStatement .= implode(" . ", $stmt) . "\r\n";
        }

        $this->record->set('exit-note', $miniStatement);

        return ExitPrompt::class;
    }
}
