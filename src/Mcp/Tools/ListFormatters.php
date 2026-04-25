<?php

namespace Javaabu\Stats\Mcp\Tools;

use Javaabu\Stats\TimeSeriesStats;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListFormatters extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'List all registered time-series stats formatters with their names and classes.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $formatters = [];

        foreach (TimeSeriesStats::formattersMap() as $name => $class) {
            $formatters[] = [
                'name' => $name,
                'class' => $class,
            ];
        }

        return Response::json($formatters);
    }
}
