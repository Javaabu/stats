<?php

namespace Javaabu\Stats\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Models\User;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;
use Javaabu\Stats\TimeSeriesStats;

class HasDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2024-07-13 12:27 PM');
    }

    /** @test */
    public function it_can_get_the_date_from_and_date_to(): void
    {
        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals('2024-07-06 00:00:00', $stat->getDateFrom()->toDateTimeString());
        $this->assertEquals('2024-07-13 23:59:59', $stat->getDateTo()->toDateTimeString());
    }

    /** @test */
    public function it_can_get_the_date_range(): void
    {
        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals(PresetDateRanges::LAST_7_DAYS, $stat->getDateRange());
    }

    /** @test */
    public function it_can_format_the_date_range(): void
    {
        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals('2024-07-06 00:00 - 2024-07-13 23:59', $stat->formattedDateRange());
    }

    /** @test */
    public function it_can_set_date_from(): void
    {
        $stat = new UserLogouts();

        $stat->setDateFrom('2024-07-05 00:00:00');

        $this->assertEquals('2024-07-05 00:00:00', $stat->getDateFrom()->toDateTimeString());
        $this->assertEquals('2024-07-05 00:00:00', $stat->getDateRange()->getDateFrom()->toDateTimeString());
    }

    /** @test */
    public function it_can_set_date_to(): void
    {
        $stat = new UserLogouts();

        $stat->setDateTo('2024-07-10 00:00:00');

        $this->assertEquals('2024-07-10 00:00:00', $stat->getDateTo()->toDateTimeString());
        $this->assertEquals('2024-07-10 00:00:00', $stat->getDateRange()->getDateTo()->toDateTimeString());
    }

    /** @test */
    public function it_can_get_date_field(): void
    {
        $stat = new UserLogouts();

        $this->assertEquals('activity_log.created_at', $stat->getDateField());
    }

    /** @test */
    public function it_can_get_max_date_and_min_date(): void
    {
        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->create([
                'created_at' => '2024-07-04 00:00:00',
            ]);

        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->create([
                'created_at' => '2024-07-08 00:00:00',
            ]);

        $stat = new UserLogouts();

        $this->assertEquals('2024-07-04 00:00:00', $stat->getMinDate()->toDateTimeString());
        $this->assertEquals('2024-07-08 00:00:00', $stat->getMaxDate()->toDateTimeString());
    }

    /** @test */
    public function it_can_set_the_date_range(): void
    {
        $stat = new UserLogouts();

        $this->assertEquals(PresetDateRanges::THIS_YEAR, $stat->getDateRange());

        $stat->setDateRange(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals(PresetDateRanges::LAST_7_DAYS, $stat->getDateRange());
        $this->assertEquals('2024-07-06 00:00:00', $stat->getDateFrom()->toDateTimeString());
        $this->assertEquals('2024-07-13 23:59:59', $stat->getDateTo()->toDateTimeString());
    }

    /** @test */
    public function it_can_apply_date_filters(): void
    {
        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->create([
                'created_at' => '2024-07-04 00:00:00',
            ]);

        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->create([
                'created_at' => '2024-07-07 00:00:00',
            ]);

        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);

        $first_log = $stat->applyDateFilters($stat->query())->first();

        $this->assertEquals('2024-07-07 00:00:00', $first_log->created_at->toDateTimeString());
    }

    /** @test */
    public function it_can_query_without_date_filters(): void
    {
        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->create([
                'created_at' => '2024-07-04 00:00:00',
            ]);

        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->create([
                'created_at' => '2024-07-07 00:00:00',
            ]);

        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);

        $first_log = $stat->filteredQueryWithoutDateFilters()->first();

        $this->assertEquals('2024-07-04 00:00:00', $first_log->created_at->toDateTimeString());
    }

    /** @test */
    public function it_can_get_the_interval_for_the_given_mode(): void
    {
        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);

        $this->assertEquals(7, $stat->interval(TimeSeriesModes::DAY));
    }

}
