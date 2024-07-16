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
        $this->assertEquals('j M y h:i A', TimeSeriesModes::HOUR->getDateFormat(), 'Invalid date format for HOUR');
        $this->assertEquals('j M y', TimeSeriesModes::DAY->getDateFormat(), 'Invalid date format for DAY');
        $this->assertEquals('Y - \W\e\e\k W', TimeSeriesModes::WEEK->getDateFormat(), 'Invalid date format for WEEK');
        $this->assertEquals('Y F', TimeSeriesModes::MONTH->getDateFormat(), 'Invalid date format for MONTH');
        $this->assertEquals('Y', TimeSeriesModes::YEAR->getDateFormat(), 'Invalid date format for YEAR');
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
        $this->assertEquals('2024, 29', TimeSeriesModes::WEEK->formatDate($date, false), 'Invalid internal formatted date for WEEK');
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
