<?php

namespace Javaabu\Stats\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsUnregistered;
use Javaabu\Stats\TimeSeriesStats;

class AbstractTimeSeriesStatsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_the_registered_metric_name(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $stat = new UserLogouts();

        $this->assertEquals('user_logouts', $stat->metric());
    }

    /** @test */
    public function it_returns_full_class_name_for_unregistered_metrics(): void
    {
        $stat = new UserLogoutsUnregistered();

        $this->assertEquals(UserLogoutsUnregistered::class, $stat->metric());
    }

    /** @test */
    public function it_can_generate_the_metric_name(): void
    {
        $stat = new UserLogouts();

        $this->assertEquals('User Logouts', $stat->getName());
    }
}
