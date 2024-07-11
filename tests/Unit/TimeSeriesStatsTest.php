<?php

namespace Javaabu\Stats\Tests\Unit;

use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;
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
}
