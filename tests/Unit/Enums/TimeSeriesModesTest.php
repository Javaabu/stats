<?php

namespace Javaabu\Stats\Tests\Unit\Enums;

use Carbon\Carbon;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Exceptions\StatsException;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TimeSeriesModesTest extends TestCase
{
    #[Test]
    public function it_can_generate_the_correct_mysql_query_for_each_mode(): void
    {
        $this->assertEquals('DATE_FORMAT(created_at, \'%Y-%m-%d %H:00\')', TimeSeriesModes::HOUR->getSql('created_at'), 'Invalid mysql query for HOUR');
        $this->assertEquals('DATE_FORMAT(created_at, \'%Y-%m-%d\')', TimeSeriesModes::DAY->getSql('created_at'), 'Invalid mysql query for DAY');
        $this->assertEquals('DATE_FORMAT(DATE_SUB(created_at, INTERVAL MOD(DAYOFWEEK(created_at) - 1 - 0 + 7, 7) DAY), \'%Y-%m-%d\')', TimeSeriesModes::WEEK->getSql('created_at'), 'Invalid mysql query for WEEK');
        $this->assertEquals('DATE_FORMAT(created_at, \'%Y-%m\')', TimeSeriesModes::MONTH->getSql('created_at'), 'Invalid mysql query for MONTH');
        $this->assertEquals('DATE_FORMAT(created_at, \'%Y\')', TimeSeriesModes::YEAR->getSql('created_at'), 'Invalid mysql query for YEAR');
        $this->assertEquals(TimeSeriesModes::DAY->getSql('created_at'), TimeSeriesModes::DAY->getSql('created_at', 'mariadb'));
    }

    #[Test]
    public function it_can_generate_the_correct_sqlite_and_postgres_queries_for_each_mode(): void
    {
        $this->assertEquals('strftime(\'%Y-%m-%d %H:00\', created_at)', TimeSeriesModes::HOUR->getSql('created_at', 'sqlite'));
        $this->assertEquals('strftime(\'%Y-%m\', created_at)', TimeSeriesModes::MONTH->getSql('created_at', 'sqlite'));
        $this->assertEquals('to_char(created_at, \'YYYY-MM-DD\')', TimeSeriesModes::DAY->getSql('created_at', 'pgsql'));
        $this->assertEquals('to_char(created_at, \'YYYY\')', TimeSeriesModes::YEAR->getSql('created_at', 'pgsql'));
    }

    #[Test]
    public function it_starts_weeks_on_monday_when_configured(): void
    {
        config()->set('stats.week_starts_on_sunday', false);

        $this->assertStringContainsString('- 1 + 7', TimeSeriesModes::WEEK->getSql('created_at'));
        $this->assertEquals('2024-07-15', TimeSeriesModes::WEEK->formatDate(Carbon::parse('2024-07-16'), false));
    }

    #[Test]
    public function it_refuses_unsupported_database_drivers(): void
    {
        $this->expectException(StatsException::class);

        TimeSeriesModes::DAY->getSql('created_at', 'sqlsrv');
    }

    #[Test]
    public function it_can_get_the_date_format_for_the_time_series_mode(): void
    {
        $this->assertEquals('D MMM YY hh:mm A', TimeSeriesModes::HOUR->getDateFormat(), 'Invalid date format for HOUR');
        $this->assertEquals('D MMM YY', TimeSeriesModes::DAY->getDateFormat(), 'Invalid date format for DAY');
        $this->assertEquals('gggg - \W\e\e\k w', TimeSeriesModes::WEEK->getDateFormat(), 'Invalid date format for WEEK');
        $this->assertEquals('YYYY MMMM', TimeSeriesModes::MONTH->getDateFormat(), 'Invalid date format for MONTH');
        $this->assertEquals('YYYY', TimeSeriesModes::YEAR->getDateFormat(), 'Invalid date format for YEAR');
    }

    #[Test]
    public function it_can_correctly_format_the_date_for_display_for_each_time_series_mode(): void
    {
        $date = Carbon::parse('2024-07-16 11:51 AM');

        $this->assertEquals('16 Jul 24 11:51 AM', TimeSeriesModes::HOUR->formatDate($date), 'Invalid formatted date for HOUR');
        $this->assertEquals('16 Jul 24', TimeSeriesModes::DAY->formatDate($date), 'Invalid formatted date for DAY');
        $this->assertEquals('2024 - Week 29', TimeSeriesModes::WEEK->formatDate($date), 'Invalid formatted date for WEEK');
        $this->assertEquals('2024 July', TimeSeriesModes::MONTH->formatDate($date), 'Invalid formatted date for MONTH');
        $this->assertEquals('2024', TimeSeriesModes::YEAR->formatDate($date), 'Invalid formatted date for YEAR');
    }

    #[Test]
    public function it_can_correctly_format_the_date_for_internal_use_for_each_time_series_mode(): void
    {
        $date = Carbon::parse('2024-07-16 11:51 AM');

        $this->assertEquals('2024-07-16 11:00', TimeSeriesModes::HOUR->formatDate($date, false), 'Invalid internal formatted date for HOUR');
        $this->assertEquals('2024-07-16', TimeSeriesModes::DAY->formatDate($date, false), 'Invalid internal formatted date for DAY');
        $this->assertEquals('2024-07-14', TimeSeriesModes::WEEK->formatDate($date, false), 'Invalid internal formatted date for WEEK');
        $this->assertEquals('2024-07', TimeSeriesModes::MONTH->formatDate($date, false), 'Invalid internal formatted date for MONTH');
        $this->assertEquals('2024', TimeSeriesModes::YEAR->formatDate($date, false), 'Invalid internal formatted date for YEAR');
    }

    #[Test]
    public function it_can_generate_the_correct_diff_method_name_for_time_series_modes(): void
    {
        $this->assertEquals('diffInHours', TimeSeriesModes::HOUR->diffMethodName(), 'Invalid diff method name for HOUR');
        $this->assertEquals('diffInDays', TimeSeriesModes::DAY->diffMethodName(), 'Invalid diff method name for DAY');
        $this->assertEquals('diffInWeeks', TimeSeriesModes::WEEK->diffMethodName(), 'Invalid diff method name for WEEK');
        $this->assertEquals('diffInMonths', TimeSeriesModes::MONTH->diffMethodName(), 'Invalid diff method name for MONTH');
        $this->assertEquals('diffInYears', TimeSeriesModes::YEAR->diffMethodName(), 'Invalid diff method name for YEAR');
    }

    #[Test]
    public function it_can_generate_the_correct_increment_method_name_for_time_series_modes(): void
    {
        $this->assertEquals('addHour', TimeSeriesModes::HOUR->incrementMethodName(), 'Invalid increment method name for HOUR');
        $this->assertEquals('addDay', TimeSeriesModes::DAY->incrementMethodName(), 'Invalid increment method name for DAY');
        $this->assertEquals('addWeek', TimeSeriesModes::WEEK->incrementMethodName(), 'Invalid increment method name for WEEK');
        $this->assertEquals('addMonth', TimeSeriesModes::MONTH->incrementMethodName(), 'Invalid increment method name for MONTH');
        $this->assertEquals('addYear', TimeSeriesModes::YEAR->incrementMethodName(), 'Invalid increment method name for YEAR');
    }

    #[Test]
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

    #[Test]
    public function it_can_correctly_increment_the_date_for_each_time_series_mode(): void
    {
        $date = Carbon::parse('2024-07-16 11:51 AM');

        $this->assertEquals('2024-07-16 12:00', TimeSeriesModes::HOUR->formatDate(TimeSeriesModes::HOUR->increment($date), false), 'Invalid incremented date for HOUR');
        $this->assertEquals('2024-07-17', TimeSeriesModes::DAY->formatDate(TimeSeriesModes::DAY->increment($date), false), 'Invalid incremented date for DAY');
        $this->assertEquals('2024-07-21', TimeSeriesModes::WEEK->formatDate(TimeSeriesModes::WEEK->increment($date), false), 'Invalid incremented date for WEEK');
        $this->assertEquals('2024-08', TimeSeriesModes::MONTH->formatDate(TimeSeriesModes::MONTH->increment($date), false), 'Invalid incremented date for MONTH');
        $this->assertEquals('2025', TimeSeriesModes::YEAR->formatDate(TimeSeriesModes::YEAR->increment($date), false), 'Invalid incremented date for YEAR');
    }
}
