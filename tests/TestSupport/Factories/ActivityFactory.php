<?php

namespace Javaabu\Stats\Tests\TestSupport\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'log_name' => 'default',
        ];
    }

    public function withUser(): ActivityFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'causer_id' => fake()->passThrough(function () {
                    $random_user = User::inRandomOrder()->value('id');

                    if (! $random_user) {
                        $random_user = User::factory()->create()->id;
                    }

                    return $random_user;
                }),
                'causer_type' => 'user'
            ];
        });
    }

    public function logout(): ActivityFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => 'logout',
            ];
        });
    }

    public function login(): ActivityFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => 'login',
            ];
        });
    }
}
