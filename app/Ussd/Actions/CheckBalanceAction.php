<?php

namespace App\Ussd\Actions;

use App\Models\Account;
use App\Models\User;
use Bmatovu\Ussd\Contracts\AnswerableTag;
use Bmatovu\Ussd\Actions\BaseAction;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Hash;

class CheckBalanceAction extends BaseAction implements AnswerableTag
{
    public function handle(): ?string
    {
        $this->shiftCursor();

        return 'Enter PIN: ';
    }

    public function process(?string $answer): void
    {
        $this->authorize($answer);

        $accountId = $this->fromCache("account_id");

        $accountLabel = $this->fromCache("account_label");

        $account = Account::findOrFail($accountId);

        throw new \Exception("Account No: {$account->number}. Bal: {$account->formattedBalance}");
    }

    protected function authorize(?string $answer): void
    {
        if ('' == $answer) {
            throw new \Exception('PIN is required.');
        }

        $user_id = $this->fromCache("user_id");

        $user = User::findOrFail($user_id);

        if(! Hash::check($answer, $user->pin_code)) {
            throw new \Exception('Wrong PIN.');
        }
    }
}
