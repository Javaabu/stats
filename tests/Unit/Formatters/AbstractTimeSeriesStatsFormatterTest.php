<?php

namespace Javaabu\Stats\Tests\Unit\Formatters;

use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\TimeSeriesStats;

class AbstractTimeSeriesStatsFormatterTest extends TestCase
{
    /** @test */
    public function it_can_get_the_registered_format_name(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class
        ]);

        $formatter = new DefaultStatsFormatter();

        $this->assertEquals('default', $formatter->getName());
    }

    /** @test */
    public function it_returns_full_class_name_for_unregistered_formatters(): void
    {
        TimeSeriesStats::registerFormatters([], false);

        $formatter = new DefaultStatsFormatter();

        $this->assertEquals(DefaultStatsFormatter::class, $formatter->getName());
    }
}
