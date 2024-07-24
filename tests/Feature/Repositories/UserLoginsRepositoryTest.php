<?php

namespace Javaabu\Stats\Tests\Feature\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\FlotStatsFormatter;
use Javaabu\Stats\Repositories\TimeSeries\UserLoginsRepository;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;

class UserLoginsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_generate_user_login_stats(): void
    {
        $this->travelTo('2024-07-04');

        // yesterday
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->login()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        // create the stat
        $stat = new UserLoginsRepository(PresetDateRanges::LAST_7_DAYS);

        $data = $stat->results(TimeSeriesModes::DAY)->toArray();

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $this->assertEquals([
            [
                'logins' => 5,
                'day' => '2024-07-03',
            ],
            [
                'logins' => 2,
                'day' => '2024-07-04',
            ]
        ], $data);
    }
}
