<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Registered but not active
        User::factory()->create([
            'phone_number' => '256772100101',
        ]);

        // Active with no accounts
        User::factory()->create([
            'phone_number' => '256772100102',
            'pin_code' => Hash::make('123456'),
        ]);

        // Saving accounts only
        User::factory()->create([
            'phone_number' => '256772100103',
            'pin_code' => Hash::make('123456'),
        ]);

        // Loans and savings accounts
        User::factory()->create([
            'phone_number' => '256772100104',
            'pin_code' => Hash::make('123456'),
        ]);
    }
}
