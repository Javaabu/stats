<?php

namespace Javaabu\Stats\Tests\Feature\Formatters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\FlotStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;

class FlotStatsFormatterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_format_with_the_flot_formatter(): void
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
        $stat = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS);
        $compare = new UserLogoutsRepository(PresetDateRanges::LAST_7_DAYS->getPreviousDateRange());

        $formatter = new FlotStatsFormatter();

        $data = $formatter->format(TimeSeriesModes::DAY, $stat, $compare);

        $this->assertIsArray($data);

        $this->assertEquals([
            [0, 0],
            [1, 0],
            [2, 0],
            [3, 0],
            [4, 0],
            [5, 5],
            [6, 2],
        ], $data);
    }
}
