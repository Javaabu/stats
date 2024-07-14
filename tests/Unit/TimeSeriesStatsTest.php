<?php

namespace Javaabu\Stats\Tests\Unit;

use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsWithPermission;
use Javaabu\Stats\TimeSeriesStats;

class TimeSeriesStatsTest extends TestCase
{
    /** @test */
    public function it_can_register_a_time_series_stat(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals('user_logouts', TimeSeriesStats::getMetricForStat(UserLogouts::class));
        $this->assertEquals(UserLogouts::class, TimeSeriesStats::getClassNameForMetric('user_logouts'));
    }

    /** @test */
    public function it_can_get_the_stats_map(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals([
            'user_logouts' => UserLogouts::class
        ], TimeSeriesStats::statsMap());
    }

    /** @test */
    public function it_can_get_the_class_name_for_a_metric(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals(UserLogouts::class, TimeSeriesStats::getClassNameForMetric('user_logouts'));
    }

    /** @test */
    public function it_can_get_the_metric_name_for_a_stat(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals('user_logouts', TimeSeriesStats::getMetricForStat(UserLogouts::class));
    }

    /** @test */
    public function it_can_create_from_a_metric(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $stat = TimeSeriesStats::createFromMetric('user_logouts', PresetDateRanges::LAST_7_DAYS);

        $this->assertInstanceOf(UserLogouts::class, $stat);
        $this->assertEquals(PresetDateRanges::LAST_7_DAYS, $stat->getDateRange());
    }

    /** @test */
    public function it_can_get_metric_classes_that_allow_all_the_given_filters(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals(['user_logouts' => UserLogouts::class], TimeSeriesStats::metricsThatAllowFilters(['user'], null, false));
        $this->assertEquals(['user_logouts' => UserLogouts::class], TimeSeriesStats::metricsThatAllowFilters('user', null, false));
        $this->assertEmpty(TimeSeriesStats::metricsThatAllowFilters(['user', 'admin'], null, false));
    }

    /** @test */
    public function it_can_get_metric_names_that_allow_all_the_given_filters(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals(['user_logouts' => 'User Logouts'], TimeSeriesStats::getMetricNames(['user']));
        $this->assertEquals(['user_logouts' => 'User Logouts'], TimeSeriesStats::getMetricNames('user'));
        $this->assertEmpty(TimeSeriesStats::getMetricNames(['user', 'admin']));
    }

    /** @test */
    public function it_can_get_the_metric_name_from_metric(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ]);

        $this->assertEquals('User Logouts', TimeSeriesStats::getMetricName('user_logouts'));
    }

    /** @test */
    public function it_can_check_if_a_guest_can_view_any_stat(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogouts::class
        ], false);

        $this->assertTrue(TimeSeriesStats::canViewAny());

        TimeSeriesStats::register([
            'user_logouts_with_permission' => UserLogoutsWithPermission::class
        ], false);

        $this->assertFalse(TimeSeriesStats::canViewAny());
    }

    /** @test */
    public function it_can_check_if_a_user_can_view_any_stat(): void
    {
        $user = User::factory()->make();

        TimeSeriesStats::register([
            'user_logouts_with_permission' => UserLogoutsWithPermission::class
        ], false);

        $this->assertFalse(TimeSeriesStats::canViewAny($user));

        Gate::define('view_stats', function (User $user) {
            return true;
        });

        $this->assertTrue(TimeSeriesStats::canViewAny($user));
    }
}
