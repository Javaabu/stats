<?php

namespace Javaabu\Stats\Tests\Feature\Formatters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\ChartjsStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\CombinedStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;

class CombinedStatsFormatterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_format_with_the_combined_formatter(): void
    {
        $this->travelTo('2024-07-04');

        // yesterday
        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->count(5)
            ->create([
                'created_at' => '2024-07-03',
            ]);

        // today
        ActivityFactory::new()
            ->logout()
            ->withUser()
            ->count(2)
            ->create([
                'created_at' => '2024-07-04',
            ]);

        // create the stat
        $stat = new UserLogouts(PresetDateRanges::LAST_7_DAYS);
        $compare = new UserLogouts(PresetDateRanges::LAST_7_DAYS->getPreviousDateRange());

        $formatter = new CombinedStatsFormatter();

        $data = $formatter->format(TimeSeriesModes::DAY, $stat, $compare);

        $this->assertIsArray($data);
        $this->assertCount(14, $data);

        $this->assertEquals([
            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-06-28',
                'logouts' => 0,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-21',
                'logouts' => 0,
            ],

            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-06-29',
                'logouts' => 0,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-22',
                'logouts' => 0,
            ],

            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-06-30',
                'logouts' => 0,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-23',
                'logouts' => 0,
            ],

            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-07-01',
                'logouts' => 0,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-24',
                'logouts' => 0,
            ],

            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-07-02',
                'logouts' => 0,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-25',
                'logouts' => 0,
            ],

            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-07-03',
                'logouts' => 5,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-26',
                'logouts' => 0,
            ],

            [
                'date_range' => '2024-06-28 00:00 - 2024-07-04 23:59',
                'day' => '2024-07-04',
                'logouts' => 2,
            ],
            [
                'date_range' => '2024-06-21 00:00 - 2024-06-27 23:59',
                'day' => '2024-06-27',
                'logouts' => 0,
            ],

        ], $data);
    }
}
