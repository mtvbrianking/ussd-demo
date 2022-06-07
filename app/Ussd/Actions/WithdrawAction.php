<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Bmatovu\Ussd\Actions\BaseAction;
use Bmatovu\Ussd\Contracts\AnswerableTag;
use Illuminate\Support\Facades\Hash;

class WithdrawAction extends BaseAction implements AnswerableTag
{
    public function handle(): ?string
    {
        $this->shiftCursor();

        return 'Enter PIN: ';
    }

    public function process(?string $answer): void
    {
        $this->authorize($answer);

        // Trigger debit towards the depositor's phone...
        // Send user an SMS when the TelCo confirms transfer

        $receiver = $this->store->get('receiver');

        $amount = $this->store->get('amount');

        throw new \Exception("Initiated withdraw of {$amount} to {$receiver}.");
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
