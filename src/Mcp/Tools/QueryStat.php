<?php

namespace Javaabu\Stats\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\TimeSeriesStats;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

#[IsReadOnly]
class QueryStat extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Query a time-series stat metric. Returns formatted results grouped by the specified time mode (hour, day, week, month, year). Use the list-metrics tool first to discover available metrics and their filters.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'metric' => $schema->string()
                ->description('The metric slug to query (e.g. "user_signups"). Use list-metrics to see available metrics.')
                ->required(),

            'mode' => $schema->string()
                ->enum(array_column(TimeSeriesModes::cases(), 'value'))
                ->description('Time grouping mode.')
                ->default('day'),

            'date_range' => $schema->string()
                ->description('Preset date range (e.g. "this_year", "last_30_days", "today"). If set, date_from and date_to are ignored.'),

            'date_from' => $schema->string()
                ->description('Custom start date (YYYY-MM-DD). Required if date_range is not set.'),

            'date_to' => $schema->string()
                ->description('Custom end date (YYYY-MM-DD). Required if date_range is not set.'),

            'format' => $schema->string()
                ->description('Output format. Available: ' . implode(', ', TimeSeriesStats::allowedFormats()) . '.')
                ->default('default'),

            'filters' => $schema->object()
                ->description('Key-value pairs of filters to apply (e.g. {"customer": 5}).'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $metric = (string) $request->get('metric');

        if (! $metric || ! array_key_exists($metric, TimeSeriesStats::statsMap())) {
            $available = implode(', ', array_keys(TimeSeriesStats::statsMap()));

            return Response::error("Unknown metric \"{$metric}\". Available metrics: {$available}");
        }

        $mode = TimeSeriesModes::tryFrom((string) ($request->get('mode') ?? 'day')) ?? TimeSeriesModes::DAY;
        $format = (string) ($request->get('format') ?? 'default');
        $filters = $request->get('filters') ?? [];

        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?? [];
        }

        try {
            $range = $this->resolveDateRange($request);
        } catch (Throwable $e) {
            return Response::error($e->getMessage());
        }

        try {
            $stats = TimeSeriesStats::createFromMetric($metric, $range, $filters);

            $result = $stats->format($format, $mode);
            $total = $stats->total();

            return Response::json([
                'metric' => $metric,
                'name' => $stats->getName(),
                'mode' => $mode->value,
                'date_from' => $stats->getDateFrom()->toDateTimeString(),
                'date_to' => $stats->getDateTo()->toDateTimeString(),
                'total' => $total,
                'format' => $format,
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            return Response::error('Query failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the date range from the request.
     */
    protected function resolveDateRange(Request $request): PresetDateRanges|ExactDateRange
    {
        $dateRange = $request->get('date_range');

        if ($dateRange) {
            $preset = PresetDateRanges::tryFrom((string) $dateRange);

            if (! $preset) {
                $available = implode(', ', array_column(PresetDateRanges::cases(), 'value'));
                throw new \InvalidArgumentException("Unknown date range \"{$dateRange}\". Available: {$available}");
            }

            return $preset;
        }

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if ($dateFrom && $dateTo) {
            return new ExactDateRange($dateFrom, $dateTo);
        }

        if ($dateFrom || $dateTo) {
            throw new \InvalidArgumentException('Both date_from and date_to are required for a custom date range.');
        }

        return PresetDateRanges::THIS_YEAR;
    }
}
