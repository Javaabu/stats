<?php

namespace Javaabu\Stats\Tests\Feature\Mcp\Tools;

use Javaabu\Stats\Mcp\Tools\ListMetrics;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\PaymentsCount;
use Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries\UserLogoutsRepository;
use Javaabu\Stats\TimeSeriesStats;
use Laravel\Mcp\Request;

class ListMetricsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Laravel\Mcp\Server\Tool::class)) {
            $this->markTestSkipped('laravel/mcp is not installed.');
        }
    }

    public function test_it_lists_registered_metrics(): void
    {
        TimeSeriesStats::register([
            'payments_count' => PaymentsCount::class,
            'user_logouts' => UserLogoutsRepository::class,
        ], false);

        $tool = new ListMetrics;
        $response = $tool->handle(new Request);

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertCount(2, $data);

        $metrics = array_column($data, 'metric');
        $this->assertContains('payments_count', $metrics);
        $this->assertContains('user_logouts', $metrics);
    }

    public function test_it_includes_metric_details(): void
    {
        TimeSeriesStats::register([
            'payments_count' => PaymentsCount::class,
        ], false);

        $tool = new ListMetrics;
        $response = $tool->handle(new Request);

        $data = json_decode((string) $response->content(), true);
        $metric = $data[0];

        $this->assertEquals('payments_count', $metric['metric']);
        $this->assertEquals(PaymentsCount::class, $metric['class']);
        $this->assertEquals('count', $metric['aggregate_field']);
        $this->assertArrayHasKey('filters', $metric);
        $this->assertArrayHasKey('name', $metric);
    }

    public function test_it_includes_filter_names(): void
    {
        TimeSeriesStats::register([
            'payments_count' => PaymentsCount::class,
        ], false);

        $tool = new ListMetrics;
        $response = $tool->handle(new Request);

        $data = json_decode((string) $response->content(), true);

        $this->assertContains('user', $data[0]['filters']);
    }

    public function test_it_returns_empty_array_when_no_metrics_registered(): void
    {
        TimeSeriesStats::register([], false);

        $tool = new ListMetrics;
        $response = $tool->handle(new Request);

        $this->assertFalse($response->isError());

        $data = json_decode((string) $response->content(), true);

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
}
