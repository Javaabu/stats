<?php

namespace Javaabu\Stats\Tests\Feature\Mcp;

use Javaabu\Stats\Mcp\StatsMcpServer;
use Javaabu\Stats\Mcp\Tools\ListFormatters;
use Javaabu\Stats\Mcp\Tools\ListMetrics;
use Javaabu\Stats\Mcp\Tools\QueryStat;
use Javaabu\Stats\Tests\TestCase;

class StatsMcpServerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Laravel\Mcp\Server\Tool::class)) {
            $this->markTestSkipped('laravel/mcp is not installed.');
        }
    }

    public function test_server_class_declares_all_tools(): void
    {
        $reflection = new \ReflectionClass(StatsMcpServer::class);
        $property = $reflection->getProperty('tools');
        $property->setAccessible(true);

        $tools = $property->getDefaultValue();

        $this->assertContains(ListMetrics::class, $tools);
        $this->assertContains(QueryStat::class, $tools);
        $this->assertContains(ListFormatters::class, $tools);
        $this->assertCount(3, $tools);
    }

    public function test_it_merges_tools_into_boost_config(): void
    {
        $tools = $this->app['config']->get('boost.mcp.tools.include', []);

        $this->assertContains(ListMetrics::class, $tools);
        $this->assertContains(QueryStat::class, $tools);
        $this->assertContains(ListFormatters::class, $tools);
    }
}
