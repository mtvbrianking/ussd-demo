<?php

namespace App\Http\Ussd;

use App\Models\User;
use App\Http\Ussd\ExitPrompt;
use Illuminate\Support\Facades\Hash;
use Sparors\Ussd\State;

/**
 * Authorize next state / action.
 */
class CheckPin extends State
{
    protected function beforeRendering(): void
    {
        $authNote = $this->record->get('auth-note', 'Enter PIN: ');

        $this->menu->text('CON ')->text($authNote);
    }

    protected function afterRendering(string $argument): void
    {
        $user = $this->record->get('user');

        if(! Hash::check($argument, $user->pin_code)) {
            $this->record->set('exit-note', 'Wrong PIN');

            $this->decision->any(ExitPrompt::class);
        }

        $this->record->set('is-authorized', true);

        $this->decision->any($this->record->get('next-step'));
    }
}
