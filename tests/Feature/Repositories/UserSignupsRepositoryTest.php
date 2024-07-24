<?php

namespace Javaabu\Stats\Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\TestUserLoginsRepository;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\TestUserSignupsRepository;

class UserSignupsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_generate_user_signup_stats(): void
    {
        $this->travelTo('2024-07-04');

        // yesterday
        User::factory()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        User::factory()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        // create the stat
        $stat = new TestUserSignupsRepository(PresetDateRanges::LAST_7_DAYS);

        $data = $stat->results(TimeSeriesModes::DAY)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'signups' => 5,
                'day' => '2024-07-03',
            ],
            [
                'signups' => 2,
                'day' => '2024-07-04',
            ]
        ], $data);
    }
}
