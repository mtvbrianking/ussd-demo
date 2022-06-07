<?php

namespace App\Ussd\Actions;

use App\Models\Account;
use App\Models\User;
use Bmatovu\Ussd\Actions\BaseAction;
use Bmatovu\Ussd\Contracts\AnswerableTag;
use Illuminate\Support\Facades\Hash;

class CheckTransactionAction extends BaseAction implements AnswerableTag
{
    public function handle(): ?string
    {
        $this->shiftCursor();

        return 'Enter PIN: ';
    }

    public function process(?string $answer): void
    {
        $this->authorize($answer);

        $accountId = $this->store->get('account_id');

        $account = Account::findOrFail($accountId);

        $transactionId = $this->store->get('transaction_id');

        $transaction = $account->transactions()->findOrFail($transactionId);

        $stmt = [
            '#' . $transaction->id,
            ucfirst($transaction->type),
            $transaction->formattedAmount,
            $transaction->created_at->format('d-m-Y H:i'),
        ];

        throw new \Exception(implode(' . ', $stmt));
    }

    protected function authorize(?string $answer): void
    {
        if ('' == $answer) {
            throw new \Exception('PIN is required.');
        }

        $user_id = $this->store->get('user_id');

        $user = User::findOrFail($user_id);

        if(! Hash::check($answer, $user->pin_code)) {
            throw new \Exception('Wrong PIN.');
        }
    }
}
