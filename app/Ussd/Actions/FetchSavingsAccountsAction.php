<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class FetchSavingsAccountsAction
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
        $user_id = $this->cache->get("{$this->prefix}_user_id");

        $user = User::findOrFail($user_id);

        $savingAccounts = $user->savingAccounts;

        if($savingAccounts->count() == 0) {
            throw new \Exception('You have no saving accounts.');
        }

        $accounts = $savingAccounts->map(function($account) {
            return [
                'id' => $account->id,
                'label' => $account->number,
            ];
        })->toJson();

        // $items = [
        //     ['id' => 1, 'label' => 'jdoe'],
        //     ['id' => '2', 'label' => 'bmatovu'],
        // ];

        // $list = new ListItems(items: $items);
        // // $list = new ListItems(['items' => $items]);

        return $accounts;
    }

    public function process(?string $answer): void
    {
    }
}
