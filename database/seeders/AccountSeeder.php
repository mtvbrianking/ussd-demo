<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // --------------------------

        $user1 = User::find(1);

        Account::factory()->create([
            'user_id' => $user1->id,
            'balance' => 60000.00,
        ]);

        // --------------------------

        $user2 = User::find(2);
        
        Account::factory()->create([
            'user_id' => $user2->id,
            'balance' => 60000.00,
        ]);

        // --------------------------

        $user3 = User::find(3);
        
        Account::factory()->create([
            'user_id' => $user3->id,
            'balance' => 60000.00,
        ]);

        Account::factory()->create([
            'user_id' => $user3->id,
            'balance' => 60000.00,
        ]);

        Account::factory()->create([
            'user_id' => $user3->id,
            'is_loan' => true,
            'balance' => -45000.00,
        ]);

        // --------------------------

        $user4 = User::find(4);
        
        Account::factory()->create([
            'user_id' => $user4->id,
            'balance' => 60000.00,
        ]);

        Account::factory()->create([
            'user_id' => $user4->id,
            'is_loan' => true,
            'balance' => -45000.00,
        ]);

        Account::factory()->create([
            'user_id' => $user4->id,
            'is_loan' => true,
            'balance' => -45000.00,
        ]);
    }
}
