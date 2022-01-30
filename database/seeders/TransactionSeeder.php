<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $savingAccounts = Account::savings()->get();

        foreach($savingAccounts as $account) {
            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => 50000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => 20000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => 25000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => 5000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => 10000.00,
            ]);
        }

        $loanAccounts = Account::loans()->get();

        foreach($loanAccounts as $account) {
            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => -100000.00,
                'description' => 'Opening loan',
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => 20000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'credit',
                'amount' => -5000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => 25000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => 5000.00,
            ]);

            Transaction::factory()->count(1)->create([
                'account_id' => $account->id,
                'type' => 'debit',
                'amount' => 10000.00,
            ]);
        }
    }
}
