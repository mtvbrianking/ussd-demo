<?php

namespace App\Http\Ussd;

use Illuminate\Support\Facades\Cache;
use Sparors\Ussd\State;

class ExitPrompt extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $exitNote = $this->record->get('exit-note', 'Thank you. Bye');

        $this->menu->text('END ')->text($exitNote);

        $this->flushCache();
    }

    protected function afterRendering(string $argument): void
    {
        // is never executed for action == prompt
    }

    protected function flushCache(): void
    {
        $sessionId = $this->record->get('sessionId');

        Cache::flushLike("ussd_{$sessionId}.%");
    }
}
