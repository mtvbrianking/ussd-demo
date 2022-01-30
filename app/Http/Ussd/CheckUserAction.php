<?php

namespace App\Http\Ussd;

use App\Models\User;
use App\Http\Ussd\ExitPrompt;
use App\Http\Ussd\ServicesScreen;
use Sparors\Ussd\Action;

class CheckUserAction extends Action
{
    public function run(): string
    {
        $phoneNumber = $this->record->get('phoneNumber');

        $user = User::where('phone_number', $phoneNumber)->first();

        if(! $user) {
            $this->record->set('exit-note', "{$phoneNumber} is not registered for this service.");

            return ExitPrompt::class;
        }

        if(! $user->pin_code) {
            $this->record->set('exit-note', "{$phoneNumber} is not activated for this service.");

            return ExitPrompt::class;
        }

        $this->record->set('user', $user);
        // $this->record->set('_user', serialize($user));

        $serviceCode = $this->record->get('serviceCode');

        if ('*308*1#' == $serviceCode) {
            return Saving\AccountsScreen::class;
        }

        if ('*308*2#' == $serviceCode) {
            return Loan\AccountsScreen::class;
        }

        return ServicesScreen::class;
    }
}
