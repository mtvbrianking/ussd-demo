<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Bmatovu\Ussd\Actions\BaseAction;
use Bmatovu\Ussd\Contracts\AnswerableTag;

class CheckUserAction extends BaseAction implements AnswerableTag
{
    public function handle(): ?string
    {
        $this->shiftCursor();

        return '';
    }

    public function process(?string $answer): void
    {
        $phoneNumber = $this->store->get('phone_number');

        $user = User::where('phone_number', $phoneNumber)->first();

        if(! $user) {
            throw new \Exception("{$phoneNumber} is not registered for this service.");
        }

        if(! $user->pin_code) {
            throw new \Exception("{$phoneNumber} is not activated for this service.");
        }

        $this->store->put('user_id', $user->id);
    }
}
