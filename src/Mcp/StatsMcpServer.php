<?php

namespace Javaabu\Stats\Mcp;

use Javaabu\Stats\Mcp\Tools\ListFormatters;
use Javaabu\Stats\Mcp\Tools\ListMetrics;
use Javaabu\Stats\Mcp\Tools\QueryStat;
use Laravel\Mcp\Server;

class StatsMcpServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Javaabu Stats';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = 'Time-series statistics server. Use list-metrics to discover available stats, then query-stat to fetch data grouped by time period. Supports multiple output formats and date range filtering.';

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ListMetrics::class,
        QueryStat::class,
        ListFormatters::class,
    ];

    /**
     * Get the tool classes registered with this server.
     *
     * @return array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    public static function toolClasses(): array
    {
        return (new \ReflectionClass(static::class))
            ->getProperty('tools')
            ->getDefaultValue();
    }
}
