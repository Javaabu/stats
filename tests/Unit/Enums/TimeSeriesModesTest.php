<?php

namespace Javaabu\Stats\Tests\Unit\Enums;

use Carbon\Carbon;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Tests\TestCase;

class TimeSeriesModesTest extends TestCase
{
    /** @test */
    public function it_can_get_the_date_format_for_the_time_series_mode(): void
    {
        $this->assertEquals('D MMM YY hh:mm A', TimeSeriesModes::HOUR->getDateFormat(), 'Invalid date format for HOUR');
        $this->assertEquals('D MMM YY', TimeSeriesModes::DAY->getDateFormat(), 'Invalid date format for DAY');
        $this->assertEquals('gggg - \W\e\e\k w', TimeSeriesModes::WEEK->getDateFormat(), 'Invalid date format for WEEK');
        $this->assertEquals('YYYY MMMM', TimeSeriesModes::MONTH->getDateFormat(), 'Invalid date format for MONTH');
        $this->assertEquals('YYYY', TimeSeriesModes::YEAR->getDateFormat(), 'Invalid date format for YEAR');
    }

    /** @test */
    public function it_can_correctly_format_the_date_for_display_for_each_time_series_mode(): void
    {
        $date = Carbon::parse('2024-07-16 11:51 AM');

        $this->assertEquals('16 Jul 24 11:51 AM', TimeSeriesModes::HOUR->formatDate($date), 'Invalid formatted date for HOUR');
        $this->assertEquals('16 Jul 24', TimeSeriesModes::DAY->formatDate($date), 'Invalid formatted date for DAY');
        $this->assertEquals('2024 - Week 29', TimeSeriesModes::WEEK->formatDate($date), 'Invalid formatted date for WEEK');
        $this->assertEquals('2024 July', TimeSeriesModes::MONTH->formatDate($date), 'Invalid formatted date for MONTH');
        $this->assertEquals('2024', TimeSeriesModes::YEAR->formatDate($date), 'Invalid formatted date for YEAR');
    }

    /** @test */
    public function it_can_correctly_format_the_date_for_internal_use_for_each_time_series_mode(): void
    {
        $date = Carbon::parse('2024-07-16 11:51 AM');

        $this->assertEquals('2024-07-16 11:51', TimeSeriesModes::HOUR->formatDate($date, false), 'Invalid internal formatted date for HOUR');
        $this->assertEquals('2024-07-16', TimeSeriesModes::DAY->formatDate($date, false), 'Invalid internal formatted date for DAY');
        $this->assertEquals('202429', TimeSeriesModes::WEEK->formatDate($date, false), 'Invalid internal formatted date for WEEK');
        $this->assertEquals('2024, 07', TimeSeriesModes::MONTH->formatDate($date, false), 'Invalid internal formatted date for MONTH');
        $this->assertEquals('2024', TimeSeriesModes::YEAR->formatDate($date, false), 'Invalid internal formatted date for YEAR');
    }

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
    public function it_can_generate_the_correct_increment_method_name_for_time_series_modes(): void
    {
        $this->assertEquals('addHour', TimeSeriesModes::HOUR->incrementMethodName(), 'Invalid increment method name for HOUR');
        $this->assertEquals('addDay', TimeSeriesModes::DAY->incrementMethodName(), 'Invalid increment method name for DAY');
        $this->assertEquals('addWeek', TimeSeriesModes::WEEK->incrementMethodName(), 'Invalid increment method name for WEEK');
        $this->assertEquals('addMonth', TimeSeriesModes::MONTH->incrementMethodName(), 'Invalid increment method name for MONTH');
        $this->assertEquals('addYear', TimeSeriesModes::YEAR->incrementMethodName(), 'Invalid increment method name for YEAR');
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

    /** @test */
    public function it_can_correctly_increment_the_date_for_each_time_series_mode(): void
    {
        $date = Carbon::parse('2024-07-16 11:51 AM');

        $this->assertEquals('2024-07-16 12:51', TimeSeriesModes::HOUR->formatDate(TimeSeriesModes::HOUR->increment($date), false), 'Invalid incremented date for HOUR');
        $this->assertEquals('2024-07-17', TimeSeriesModes::DAY->formatDate(TimeSeriesModes::DAY->increment($date), false), 'Invalid incremented date for DAY');
        $this->assertEquals('202430', TimeSeriesModes::WEEK->formatDate(TimeSeriesModes::WEEK->increment($date), false), 'Invalid incremented date for WEEK');
        $this->assertEquals('2024, 08', TimeSeriesModes::MONTH->formatDate(TimeSeriesModes::MONTH->increment($date), false), 'Invalid incremented date for MONTH');
        $this->assertEquals('2025', TimeSeriesModes::YEAR->formatDate(TimeSeriesModes::YEAR->increment($date), false), 'Invalid incremented date for YEAR');
    }
}
