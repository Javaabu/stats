<?php

namespace Javaabu\Stats\Tests\Unit;

use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Tests\TestCase;

class PresetDateRangesTest extends TestCase
{
    /** @test */
    public function it_can_generate_correct_start_date(): void
    {
        $this->travelTo('2024-07-08 10:07 PM');

        $this->assertEquals('2024-07-08 00:00:00', PresetDateRanges::TODAY->getDateFrom(), 'Incorrect start of TODAY');
        $this->assertEquals('2024-07-07 00:00:00', PresetDateRanges::YESTERDAY->getDateFrom(), 'Incorrect start of YESTERDAY');
        $this->assertEquals('2024-07-07 00:00:00', PresetDateRanges::THIS_WEEK->getDateFrom(), 'Incorrect start of THIS_WEEK');
        $this->assertEquals('2024-06-30 00:00:00', PresetDateRanges::LAST_WEEK->getDateFrom(), 'Incorrect start of LAST_WEEK');
        $this->assertEquals('2024-07-01 00:00:00', PresetDateRanges::THIS_MONTH->getDateFrom(), 'Incorrect start of THIS_MONTH');
        $this->assertEquals('2024-06-01 00:00:00', PresetDateRanges::LAST_MONTH->getDateFrom(), 'Incorrect start of LAST_MONTH');
        $this->assertEquals('2024-01-01 00:00:00', PresetDateRanges::THIS_YEAR->getDateFrom(), 'Incorrect start of THIS_YEAR');
        $this->assertEquals('2023-01-01 00:00:00', PresetDateRanges::LAST_YEAR->getDateFrom(), 'Incorrect start of LAST_YEAR');
        $this->assertEquals('2024-07-01 00:00:00', PresetDateRanges::LAST_7_DAYS->getDateFrom(), 'Incorrect start of LAST_7_DAYS');
        $this->assertEquals('2024-06-24 00:00:00', PresetDateRanges::LAST_14_DAYS->getDateFrom(), 'Incorrect start of LAST_14_DAYS');
        $this->assertEquals('2024-06-08 00:00:00', PresetDateRanges::LAST_30_DAYS->getDateFrom(), 'Incorrect start of LAST_30_DAYS');
        $this->assertEquals('2022-07-08 22:07:00', PresetDateRanges::LIFETIME->getDateFrom(now()->subYears(2)), 'Incorrect start of LIFETIME');
    }

    /** @test */
    public function it_can_generate_correct_end_date(): void
    {
        $this->travelTo('2024-07-08 10:07 PM');

        $this->assertEquals('2024-07-08 23:59:59', PresetDateRanges::TODAY->getDateTo(), 'Incorrect end of TODAY');
        $this->assertEquals('2024-07-07 23:59:59', PresetDateRanges::YESTERDAY->getDateTo(), 'Incorrect end of YESTERDAY');
        $this->assertEquals('2024-07-13 23:59:59', PresetDateRanges::THIS_WEEK->getDateTo(), 'Incorrect end of THIS_WEEK');
        $this->assertEquals('2024-07-06 23:59:59', PresetDateRanges::LAST_WEEK->getDateTo(), 'Incorrect end of LAST_WEEK');
        $this->assertEquals('2024-07-31 23:59:59', PresetDateRanges::THIS_MONTH->getDateTo(), 'Incorrect end of THIS_MONTH');
        $this->assertEquals('2024-06-30 23:59:59', PresetDateRanges::LAST_MONTH->getDateTo(), 'Incorrect end of LAST_MONTH');
        $this->assertEquals('2024-12-31 23:59:59', PresetDateRanges::THIS_YEAR->getDateTo(), 'Incorrect end of THIS_YEAR');
        $this->assertEquals('2023-12-31 23:59:59', PresetDateRanges::LAST_YEAR->getDateTo(), 'Incorrect end of LAST_YEAR');
        $this->assertEquals('2024-07-08 23:59:59', PresetDateRanges::LAST_7_DAYS->getDateTo(), 'Incorrect end of LAST_7_DAYS');
        $this->assertEquals('2024-07-08 23:59:59', PresetDateRanges::LAST_14_DAYS->getDateTo(), 'Incorrect end of LAST_14_DAYS');
        $this->assertEquals('2024-07-08 23:59:59', PresetDateRanges::LAST_30_DAYS->getDateTo(), 'Incorrect end of LAST_30_DAYS');
        $this->assertEquals('2024-07-08 22:07:00', PresetDateRanges::LIFETIME->getDateTo(), 'Incorrect end of LIFETIME');
    }
}
