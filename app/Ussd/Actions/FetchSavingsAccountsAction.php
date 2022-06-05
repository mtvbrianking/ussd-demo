<?php

namespace App\Ussd\Actions;

use App\Models\User;
use Bmatovu\Ussd\Actions\BaseAction;
use Bmatovu\Ussd\Contracts\AnswerableTag;
use Illuminate\Contracts\Cache\Repository as CacheContract;

class FetchSavingsAccountsAction extends BaseAction
{
    public function handle(): ?string
    {
        $this->shiftCursor();

        $user_id = $this->fromCache("user_id");

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
}
