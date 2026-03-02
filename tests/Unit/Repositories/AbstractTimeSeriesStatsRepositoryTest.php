<?php

namespace Javaabu\Stats\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsUnregistered;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsWithPermission;
use Javaabu\Stats\TimeSeriesStats;

class AbstractTimeSeriesStatsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_get_the_registered_metric_name(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $stat = new UserLogoutsRepository();

        $this->assertEquals('user_logouts', $stat->metric());
    }

    public function test_it_returns_full_class_name_for_unregistered_metrics(): void
    {
        $stat = new UserLogoutsUnregistered();

        $this->assertEquals(UserLogoutsUnregistered::class, $stat->metric());
    }

    public function test_it_can_generate_the_metric_name(): void
    {
        $stat = new UserLogoutsRepository();

        $this->assertEquals('User Logouts', $stat->getName());
    }

    public function test_it_can_check_if_a_given_user_can_view_the_stats(): void
    {
        $stat = new UserLogoutsRepository();

        $this->assertTrue($stat->canView());

        $stat = new UserLogoutsWithPermission();

        $this->assertFalse($stat->canView());
    }

    public function test_it_can_generate_the_aggregate_field_label(): void
    {
        $stat = new UserLogoutsRepository();

        $this->assertEquals('Logouts', $stat->getAggregateFieldLabel());
    }
}
