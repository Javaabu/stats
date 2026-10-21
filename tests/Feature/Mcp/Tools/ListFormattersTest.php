<?php

namespace Javaabu\Stats\Tests\Feature\Mcp\Tools;

use Javaabu\Stats\Formatters\TimeSeries\ChartjsStatsFormatter;
use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Mcp\Tools\ListFormatters;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\TimeSeriesStats;
use Laravel\Mcp\Request;

class ListFormattersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Laravel\Mcp\Server\Tool::class)) {
            $this->markTestSkipped('laravel/mcp is not installed.');
        }
    }

    public function test_it_lists_registered_formatters(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class,
            'chartjs' => ChartjsStatsFormatter::class,
        ], false);

        $tool = new ListFormatters;
        $response = $tool->handle(new Request);

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertCount(2, $data);

        $names = array_column($data, 'name');
        $this->assertContains('default', $names);
        $this->assertContains('chartjs', $names);
    }

    public function test_it_includes_formatter_class(): void
    {
        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class,
        ], false);

        $tool = new ListFormatters;
        $response = $tool->handle(new Request);

        $data = json_decode((string) $response->content(), true);

        $this->assertEquals('default', $data[0]['name']);
        $this->assertEquals(DefaultStatsFormatter::class, $data[0]['class']);
    }

    public function test_it_returns_empty_array_when_no_formatters_registered(): void
    {
        TimeSeriesStats::registerFormatters([], false);

        $tool = new ListFormatters;
        $response = $tool->handle(new Request);

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}
