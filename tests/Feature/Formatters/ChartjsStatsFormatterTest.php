<?php

namespace Javaabu\Stats\Tests\Feature\Formatters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\ChartjsStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;

class ChartjsStatsFormatterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_format_with_the_chartjs_formatter(): void
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

        $formatter = new ChartjsStatsFormatter();

        $data = $formatter->format(TimeSeriesModes::DAY, $stat, $compare);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('compare', $data);

        $labels = $data['labels'];
        $stat_data = $data['stats'];
        $compare_data = $data['compare'];

        $this->assertEquals([
            '28 Jun 24#21 Jun 24',
            '29 Jun 24#22 Jun 24',
            '30 Jun 24#23 Jun 24',
            '1 Jul 24#24 Jun 24',
            '2 Jul 24#25 Jun 24',
            '3 Jul 24#26 Jun 24',
            '4 Jul 24#27 Jun 24',
        ], $labels);

        $this->assertEquals([
            0,
            0,
            0,
            0,
            0,
            5,
            2,
        ], $stat_data);

        $this->assertEquals([
            0,
            0,
            0,
            0,
            0,
            0,
            0,
        ], $compare_data);
    }
}
