<?php

namespace Javaabu\Stats\Tests\Feature\Formatters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\ActivityFactory;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogouts;

class DefaultStatsFormatterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_format_with_the_default_formatter(): void
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

        $formatter = new DefaultStatsFormatter();

        $data = $formatter->format(TimeSeriesModes::DAY, $stat);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('compare', $data);

        $stat_data = $data['stats']->toArray();

        $this->assertCount(2, $stat_data);

        $this->assertContains([
            'logouts' => 5,
            'day' => '2024-07-03',
        ], $stat_data);

        $this->assertContains([
            'logouts' => 2,
            'day' => '2024-07-04',
        ], $stat_data);
    }
}
