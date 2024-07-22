<?php

namespace Javaabu\Stats\Tests\TestSupport\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 0, 20000),
            'paid_at' => fake()->optional()->dateTime(now()->toDateTimeString()),
        ];
    }

    public function withUser(User|int|null $user = null): PaymentFactory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => fake()->passThrough(function () use ($user) {
                    if ($user) {
                        return $user instanceof User ? $user->id : $user;
                    }

                    $random_user = User::inRandomOrder()->value('id');

                    if (! $random_user) {
                        $random_user = User::factory()->create()->id;
                    }

                    return $random_user;
                }),
            ];
        });
    }
}
