<?php

namespace App\Ussd\Actions;

use App\Models\Account;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Support\Facades\Hash;

class WithdrawAction
{
    protected \DOMNode $node;
    protected CacheContract $cache;
    protected string $prefix;
    protected int $ttl;

    public function __construct(\DOMNode $node, CacheContract $cache, string $prefix, ?int $ttl = null)
    {
        $this->node = $node;
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    public function handle(): ?string
    {
        // return $this->node->attributes->getNamedItem('text')->nodeValue;

        return 'Enter PIN: ';
    }

    public function process(?string $answer): void
    {
        $this->authorize($answer);

        // Trigger debit towards the depositor's phone...
        // Send user an SMS when the TelCo confirms transfer

        $receiver = $this->cache->get("{$this->prefix}_receiver");

        $amount = $this->cache->get("{$this->prefix}_amount");

        throw new \Exception("Initiated withdraw of {$amount} to {$receiver}.");
    }

    protected function authorize(?string $answer): void
    {
        if ('' == $answer) {
            throw new \Exception('PIN is required.');
        }

        $user_id = $this->cache->get("{$this->prefix}_user_id");

        $user = User::findOrFail($user_id);

        if(! Hash::check($answer, $user->pin_code)) {
            throw new \Exception('Wrong PIN.');
        }
    }
}
