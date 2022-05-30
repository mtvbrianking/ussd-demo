<?php

namespace App\Ussd\Actions;

use App\Models\Account;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CheckBalanceAction
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

    public function __invoke(\DOMNode $node): void
    {
        $accountId = $this->cache->get("{$this->prefix}_account_id");

        $accountLabel = $this->cache->get("{$this->prefix}_account_label");

        $account = Account::find($accountId);

        if(! $account) {
            throw new \Exception("Account No: {$accountLabel} not found.");
        }

        throw new \Exception("Account No: {$account->number}. Bal: {$account->formattedBalance}");
    }
}
