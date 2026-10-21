<?php

namespace Javaabu\Stats\Mcp\Tools;

use Javaabu\Stats\TimeSeriesStats;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListMetrics extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'List all registered time-series stat metrics with their names, classes, aggregate fields, and allowed filters.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $metrics = [];

        foreach (TimeSeriesStats::statsMap() as $slug => $class) {
            $stat = TimeSeriesStats::createFromMetric($slug);

            $filters = array_map(fn ($filter) => $filter->getName(), $stat->allowedFilters());

            $metrics[] = [
                'metric' => $slug,
                'name' => $stat->getName(),
                'class' => $class,
                'aggregate_field' => $stat->getAggregateFieldName(),
                'filters' => $filters,
            ];
        }

        return Response::json($metrics);
    }
}
