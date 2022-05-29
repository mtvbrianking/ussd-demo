<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class FetchSavingsAccountsAction
{
    protected CacheContract $cache;
    protected string $prefix;
    protected int $ttl;

    public function __construct(CacheContract $cache, string $prefix, ?int $ttl = null)
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    public function __invoke(\DOMNode $node): array
    {
        $user_id = $this->cache->get("{$this->prefix}_user_id");

        $user = User::findOrFail($user_id);

        $savingAccounts = $user->savingAccounts;

        if($savingAccounts->count() == 0) {
            throw new \Exception('You have no saving accounts.');
        }

        // $this->cache->put("{$this->prefix}_saving_accounts", $savingAccounts, $this->ttl);

        return $savingAccounts->map(function($account) {
            return [
                'id' => $account->id,
                'label' => $account->number,
            ];
        })->toArray();
    }
}
