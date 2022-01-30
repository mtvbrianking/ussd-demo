<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => function () {
                return AccountFactory::new()->create()->id;
            },
            'amount' => $this->faker->numberBetween(1000, 10000),
            'type' => $this->faker->randomElement([
                'debit',
                'credit',
            ])
        ];
    }
}
