<?php

namespace App\Ussd\Providers;

use App\Models\User;
use Bmatovu\Ussd\Dto\ListItems;
use Bmatovu\Ussd\Store;
use Bmatovu\Ussd\Contracts\ListProvider;

class SavingAccountsProvider implements ListProvider
{
    protected Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function load(): array
    {
        $user_id = $this->store->get('user_id');

        $user = User::findOrFail($user_id);

        $savingAccounts = $user->savingAccounts;

        if($savingAccounts->count() == 0) {
            throw new \Exception('You have no saving accounts.');
        }

        return $savingAccounts->map(function($account) {
            return [
                'id' => $account->id,
                'label' => $account->number,
            ];
        })->toArray();
    }
}
