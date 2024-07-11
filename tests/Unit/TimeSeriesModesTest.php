<?php

namespace Javaabu\Stats\Tests\Unit;

use Carbon\Carbon;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Tests\TestCase;

class TimeSeriesModesTest extends TestCase
{
    /** @test */
    public function it_can_generate_the_correct_diff_method_name_for_time_series_modes(): void
    {
        $this->assertEquals('diffInHours', TimeSeriesModes::HOUR->diffMethodName(), 'Invalid diff method name for HOUR');
        $this->assertEquals('diffInDays', TimeSeriesModes::DAY->diffMethodName(), 'Invalid diff method name for DAY');
        $this->assertEquals('diffInWeeks', TimeSeriesModes::WEEK->diffMethodName(), 'Invalid diff method name for WEEK');
        $this->assertEquals('diffInMonths', TimeSeriesModes::MONTH->diffMethodName(), 'Invalid diff method name for MONTH');
        $this->assertEquals('diffInYears', TimeSeriesModes::YEAR->diffMethodName(), 'Invalid diff method name for YEAR');
    }

    /** @test */
    public function it_can_generate_the_correct_interval_for_time_series_modes(): void
    {
        $date_from = Carbon::parse('2024-07-09 18:34:00');
        $date_to = Carbon::parse('2025-07-09 18:34:00');

        $this->assertEquals(8760, TimeSeriesModes::HOUR->interval($date_from, $date_to), 'Invalid interval for HOUR');
        $this->assertEquals(365, TimeSeriesModes::DAY->interval($date_from, $date_to), 'Invalid interval for DAY');
        $this->assertEquals(52, TimeSeriesModes::WEEK->interval($date_from, $date_to), 'Invalid interval for WEEK');
        $this->assertEquals(12, TimeSeriesModes::MONTH->interval($date_from, $date_to), 'Invalid interval for MONTH');
        $this->assertEquals(1, TimeSeriesModes::YEAR->interval($date_from, $date_to), 'Invalid interval for YEAR');
    }
}
