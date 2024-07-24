<?php

namespace Javaabu\Stats\Tests\Unit;

use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\StatListReturnType;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsWithPermission;
use Javaabu\Stats\TimeSeriesStats;

class TimeSeriesStatsTest extends TestCase
{
    /** @test */
    public function it_can_register_a_time_series_stat_formatter(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class
        ]);

        $this->assertEquals('default', TimeSeriesStats::getNameForFormatter(DefaultStatsFormatter::class));
        $this->assertEquals(DefaultStatsFormatter::class, TimeSeriesStats::getClassNameForFormat('default'));
    }

    /** @test */
    public function it_can_get_the_formatters_map(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class
        ]);

        $this->assertEquals([
            'default' => DefaultStatsFormatter::class
        ], TimeSeriesStats::formattersMap());
    }

    /** @test */
    public function it_can_get_the_class_name_for_a_formatter(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class
        ]);

        $this->assertEquals(DefaultStatsFormatter::class, TimeSeriesStats::getClassNameForFormat('default'));
    }

    /** @test */
    public function it_can_get_the_name_for_a_formatter(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class
        ]);

        $this->assertEquals('default', TimeSeriesStats::getNameForFormatter(DefaultStatsFormatter::class));
    }

    /** @test */
    public function it_can_create_from_a_formatter(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class
        ]);

        $formatter = TimeSeriesStats::createFromFormat('default');

        $this->assertInstanceOf(DefaultStatsFormatter::class, $formatter);
        $this->assertEquals('default', $formatter->getName());
    }

    /** @test */
    public function it_can_register_a_time_series_stat(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals('user_logouts', TimeSeriesStats::getMetricForStat(UserLogoutsRepository::class));
        $this->assertEquals(UserLogoutsRepository::class, TimeSeriesStats::getClassNameForMetric('user_logouts'));
    }

    /** @test */
    public function it_can_get_the_stats_map(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals([
            'user_logouts' => UserLogoutsRepository::class
        ], TimeSeriesStats::statsMap());
    }

    /** @test */
    public function it_can_get_the_class_name_for_a_metric(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals(UserLogoutsRepository::class, TimeSeriesStats::getClassNameForMetric('user_logouts'));
    }

    /** @test */
    public function it_can_get_the_metric_name_for_a_stat(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals('user_logouts', TimeSeriesStats::getMetricForStat(UserLogoutsRepository::class));
    }

    /** @test */
    public function it_can_create_from_a_metric(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $stat = TimeSeriesStats::createFromMetric('user_logouts', PresetDateRanges::LAST_7_DAYS);

        $this->assertInstanceOf(UserLogoutsRepository::class, $stat);
        $this->assertEquals(PresetDateRanges::LAST_7_DAYS, $stat->getDateRange());
    }

    /** @test */
    public function it_can_get_metrics_that_allow_all_the_given_filters(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals(['user_logouts'], TimeSeriesStats::metricsThatAllowFilters(['user'], null, StatListReturnType::METRIC));
        $this->assertEquals(['user_logouts'], TimeSeriesStats::metricsThatAllowFilters('user', null, StatListReturnType::METRIC));
        $this->assertEmpty(TimeSeriesStats::metricsThatAllowFilters(['user', 'admin'], null, StatListReturnType::METRIC));
    }

    /** @test */
    public function it_can_get_metric_classes_that_allow_all_the_given_filters(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals(['user_logouts' => UserLogoutsRepository::class], TimeSeriesStats::metricsThatAllowFilters(['user'], null, StatListReturnType::METRIC_AND_CLASS));
        $this->assertEquals(['user_logouts' => UserLogoutsRepository::class], TimeSeriesStats::metricsThatAllowFilters('user', null, StatListReturnType::METRIC_AND_CLASS));
        $this->assertEmpty(TimeSeriesStats::metricsThatAllowFilters(['user', 'admin'], null, StatListReturnType::METRIC_AND_CLASS));
    }

    /** @test */
    public function it_can_get_metric_names_that_allow_all_the_given_filters(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals(['user_logouts' => 'User Logouts'], TimeSeriesStats::getMetricNames(['user']));
        $this->assertEquals(['user_logouts' => 'User Logouts'], TimeSeriesStats::getMetricNames('user'));
        $this->assertEmpty(TimeSeriesStats::getMetricNames(['user', 'admin']));
    }

    /** @test */
    public function it_can_get_the_metric_name_from_metric(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
        ]);

        $this->assertEquals('User Logouts', TimeSeriesStats::getMetricName('user_logouts'));
    }

    /** @test */
    public function it_can_check_if_a_guest_can_view_any_stat(): void
    {
        TimeSeriesStats::register([
            'user_logouts' => UserLogoutsRepository::class
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
