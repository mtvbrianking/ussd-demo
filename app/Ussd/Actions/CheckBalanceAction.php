<?php

namespace App\Ussd\Actions;

use App\Models\Account;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class CheckBalanceAction
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
        return '';
    }

    public function process(?string $answer): void
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
