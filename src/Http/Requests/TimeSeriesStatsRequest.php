<?php

namespace Javaabu\Stats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Http\Requests\Concerns\ResolvesStats;
use Javaabu\Stats\TimeSeriesStats;

/**
 * A time series stat for a metric, mode and date range, optionally compared
 * with another period. See {@see ResolvesStats} for the date parameters.
 */
class TimeSeriesStatsRequest extends FormRequest
{
    use ResolvesStats;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $metrics = TimeSeriesStats::allowedMetrics($this->fixedFilters(), $this->statsUser());

        return [
            'metric' => ['required', 'string', Rule::in($metrics)],
            'with' => ['nullable', 'array'],
            'with.*' => ['string', Rule::in($metrics)],
            'mode' => ['required', 'string', Rule::enum(TimeSeriesModes::class)],
            'format' => ['nullable', 'string', Rule::in(TimeSeriesStats::allowedFormats())],
            ...$this->dateRangeRules(),
        ];
    }

    public function metric(): string
    {
        return (string) $this->input('metric');
    }

    /**
     * Other metrics to plot with the main one
     *
     * @return list<string>
     */
    public function otherMetrics(): array
    {
        return array_values(array_diff(array_unique((array) $this->input('with', [])), [$this->metric()]));
    }

    public function mode(): TimeSeriesModes
    {
        return TimeSeriesModes::tryFrom((string) $this->input('mode')) ?? TimeSeriesStats::defaultMode();
    }

    /**
     * The stat for the date range, with its filters
     */
    public function stats(?string $metric = null): TimeSeriesStatsRepository
    {
        return $this->makeStat($metric ?? $this->metric(), $this->dateRange());
    }

    /**
     * The stat for the comparison period, if there is one
     */
    public function compareStats(?string $metric = null): ?TimeSeriesStatsRepository
    {
        $range = $this->compareDateRange();

        return $range ? $this->makeStat($metric ?? $this->metric(), $range) : null;
    }

    protected function makeStat(string $metric, $range): TimeSeriesStatsRepository
    {
        $filters = $this->filtersFor(TimeSeriesStats::createFromMetric($metric, filters: $this->fixedFilters()));

        return TimeSeriesStats::createFromMetric($metric, $range, $filters);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'metric' => [
                'description' => 'Which metric to use for the stat. The available metrics depend on the user\'s permissions and the filters used.',
                'example' => TimeSeriesStats::allowedMetrics([], $this->statsUser())[0] ?? '',
            ],
            'mode' => [
                'description' => 'How to group the data: `hour`, `day`, `week`, `month` or `year`.',
                'example' => TimeSeriesModes::MONTH->value,
            ],
            'date_range' => [
                'description' => 'Preset date range to filter results by, or `custom` to use date_from and date_to.',
                'example' => 'this_year',
            ],
            'date_from' => [
                'description' => 'Start of a custom date range.',
            ],
            'date_to' => [
                'description' => 'End of a custom date range. A date without a time includes the whole day.',
            ],
            'compare' => [
                'description' => 'Compare with the previous period.',
                'example' => true,
            ],
            'compare_to' => [
                'description' => '`previous` for the previous period, or `custom` for compare_date_from and compare_date_to.',
            ],
            'compare_date_range' => [
                'description' => 'Preset date range to compare with.',
                'example' => 'No-example',
            ],
            'compare_date_from' => [
                'description' => 'Start of a custom comparison period.',
            ],
            'compare_date_to' => [
                'description' => 'End of a custom comparison period.',
            ],
            'format' => [
                'description' => 'Which format to return the results in.',
            ],
            'filters' => [
                'description' => 'Filters to apply to the stat.',
            ],
        ];
    }
}
