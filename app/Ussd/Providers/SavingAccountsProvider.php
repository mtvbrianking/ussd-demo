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

    public function load(): ListItems
    {
        $user_id = $this->store->get('user_id');

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
        })->toArray();

        return new ListItems(['items' => $accounts]);
    }
}

// $item = new Item(['id' => 1, 'label' => 'jdoe']);

// $items = [
//     ['id' => 1, 'label' => 'jdoe'],
//     ['id' => '2', 'label' => 'bmatovu'],
// ];

// $list1 = new ListItems(items: $items);

// $_list1 = $list1->toArray();

// $list2 = new ListItems($_list1);

// $list2->items[1];
