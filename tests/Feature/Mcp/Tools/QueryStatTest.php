<?php

namespace Javaabu\Stats\Tests\Feature\Mcp\Tools;

use Javaabu\Stats\Formatters\TimeSeries\DefaultStatsFormatter;
use Javaabu\Stats\Mcp\Tools\QueryStat;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsCount;
use Javaabu\Stats\TimeSeriesStats;
use Laravel\Mcp\Request;

class QueryStatTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Laravel\Mcp\Server\Tool::class)) {
            $this->markTestSkipped('laravel/mcp is not installed.');
        }

        TimeSeriesStats::register([
            'payments_count' => PaymentsCount::class,
        ], false);

        TimeSeriesStats::registerFormatters([
            'default' => DefaultStatsFormatter::class,
        ], false);
    }

    public function test_it_returns_error_for_unknown_metric(): void
    {
        $tool = new QueryStat;
        $response = $tool->handle(new Request(['metric' => 'nonexistent']));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Unknown metric', (string) $response->content());
        $this->assertStringContainsString('payments_count', (string) $response->content());
    }

    public function test_it_returns_error_for_empty_metric(): void
    {
        $tool = new QueryStat;
        $response = $tool->handle(new Request([]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Unknown metric', (string) $response->content());
    }

    public function test_it_returns_error_for_invalid_date_range(): void
    {
        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
            'date_range' => 'invalid_range',
        ]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Unknown date range', (string) $response->content());
    }

    public function test_it_returns_error_for_partial_date_range(): void
    {
        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
            'date_from' => '2024-01-01',
        ]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Both date_from and date_to are required', (string) $response->content());
    }

    public function test_it_queries_stat_with_preset_date_range(): void
    {
        if (! extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension required.');
        }

        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
            'date_range' => 'this_year',
            'mode' => 'month',
        ]));

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertEquals('payments_count', $data['metric']);
        $this->assertEquals('month', $data['mode']);
        $this->assertEquals('default', $data['format']);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('result', $data);
        $this->assertArrayHasKey('date_from', $data);
        $this->assertArrayHasKey('date_to', $data);
    }

    public function test_it_queries_stat_with_custom_date_range(): void
    {
        if (! extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension required.');
        }

        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]));

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertEquals('payments_count', $data['metric']);
        $this->assertStringContainsString('2024-01-01', $data['date_from']);
        $this->assertStringContainsString('2024-12-31', $data['date_to']);
    }

    public function test_it_defaults_to_this_year_when_no_date_provided(): void
    {
        if (! extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension required.');
        }

        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
        ]));

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertStringContainsString(date('Y') . '-01-01', $data['date_from']);
    }

    public function test_it_defaults_to_day_mode(): void
    {
        if (! extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension required.');
        }

        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
            'date_range' => 'today',
        ]));

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertEquals('day', $data['mode']);
    }

    public function test_it_returns_error_on_query_failure(): void
    {
        if (extension_loaded('pdo')) {
            $this->markTestSkipped('This test verifies graceful error handling when DB is unavailable.');
        }

        $tool = new QueryStat;
        $response = $tool->handle(new Request([
            'metric' => 'payments_count',
            'date_range' => 'this_year',
        ]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('Query failed', (string) $response->content());
    }
}
