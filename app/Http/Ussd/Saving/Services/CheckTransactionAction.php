<?php

namespace App\Http\Ussd\Saving\Services;

use App\Models\User;
use App\Http\Ussd\CheckPin;
use App\Http\Ussd\ExitPrompt;
use Sparors\Ussd\Action;

class CheckTransactionAction extends Action
{
    public function run(): string
    {
        if(! $this->record->get('is-authorized', false)) {
            $this->record->set('auth-note', 'Authorize Check Transaction. Enter PIN: ');

            $this->record->set('next-step', self::class);

            return CheckPin::class;
        }

        $savingAccount = $this->record->get('saving-account');

        $transactionNumber = $this->record->get('transaction-number');

        $transaction = $savingAccount->transactions()->find($transactionNumber);

        if(! $transaction) {
            $this->record->set('exit-note', "Account: $savingAccount->number. Unknown Transaction: #{$transactionNumber}");

            return ExitPrompt::class;
        }

        $stmt = [
            '#' . $transaction->id,
            ucfirst($transaction->type),
            $transaction->formattedAmount,
            $transaction->created_at->format('d-m-Y H:i'),
        ];

        $this->record->set('exit-note', implode(" . ", $stmt));

        return ExitPrompt::class;
    }
}
