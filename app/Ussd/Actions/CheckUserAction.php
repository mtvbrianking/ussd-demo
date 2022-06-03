<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Bmatovu\Ussd\Actions\BaseAction;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CheckUserAction extends BaseAction
{
    public function handle(): ?string
    {
        $this->shiftCursor();

        return '';
    }

    public function process(?string $answer): void
    {
        $phoneNumber = $this->fromCache("phone_number");

        $user = User::where('phone_number', $phoneNumber)->first();

        if(! $user) {
            throw new \Exception("{$phoneNumber} is not registered for this service.");
        }

        if(! $user->pin_code) {
            throw new \Exception("{$phoneNumber} is not activated for this service.");
        }

        $this->toCache("user_id", $user->id);
    }
}
