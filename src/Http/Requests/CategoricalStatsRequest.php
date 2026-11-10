<?php

namespace Javaabu\Stats\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Http\Requests\Concerns\ResolvesStats;

/**
 * A categorical stat for a metric, category mode and date range, optionally
 * compared with another period. See {@see ResolvesStats} for the date parameters.
 */
class CategoricalStatsRequest extends FormRequest
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
        $metrics = CategoricalStats::allowedMetrics($this->fixedFilters(), $this->statsUser());

        return [
            'metric' => ['required', 'string', Rule::in($metrics)],
            'with' => ['nullable', 'array'],
            'with.*' => ['string', Rule::in($metrics)],
            'mode' => ['required', 'string', Rule::enum(CategoricalModes::class)],
            'categorical_values' => ['required_if:mode,' . CategoricalModes::SPECIFIC->value, 'array', 'min:1'],
            'categorical_values.*' => ['required'],
            'format' => ['nullable', 'string', Rule::in(CategoricalStats::allowedFormats())],
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

    public function mode(): CategoricalModes
    {
        return CategoricalModes::tryFrom((string) $this->input('mode')) ?? CategoricalStats::defaultMode();
    }

    /**
     * @return list<int|string>
     */
    public function categoricalValues(): array
    {
        return array_values((array) $this->input('categorical_values', []));
    }

    public function stats(?string $metric = null): CategoricalStatsRepository
    {
        return $this->makeStat($metric ?? $this->metric(), $this->dateRange());
    }

    public function compareStats(?string $metric = null): ?CategoricalStatsRepository
    {
        $range = $this->compareDateRange();

        return $range ? $this->makeStat($metric ?? $this->metric(), $range) : null;
    }

    protected function makeStat(string $metric, $range): CategoricalStatsRepository
    {
        $filters = $this->filtersFor(CategoricalStats::createFromMetric($metric, filters: $this->fixedFilters()));

        return CategoricalStats::createFromMetric($metric, $range, $filters);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'metric' => [
                'description' => 'The registered metric. Availability depends on user permissions and requested filters.',
                'example' => CategoricalStats::allowedMetrics([], $this->statsUser())[0] ?? '',
            ],
            'mode' => [
                'description' => 'Categories to include: non_empty, all, or specific.',
                'example' => CategoricalModes::NON_EMPTY->value,
            ],
            'categorical_values.*' => [
                'description' => 'Category ids. Required when mode is specific.',
            ],
            'date_range' => [
                'description' => 'Preset date range used to filter results, or `custom` to use date_from and date_to.',
                'example' => 'this_year',
            ],
            'date_from' => [
                'description' => 'Start of a custom date range.',
            ],
            'date_to' => [
                'description' => 'End of a custom date range. A date without a time includes the whole day.',
            ],
            'compare' => [
                'description' => 'Whether to compare with the previous period.',
                'example' => true,
            ],
            'compare_to' => [
                'description' => '`previous` for the previous period, or `custom` for compare_date_from and compare_date_to.',
            ],
            'compare_date_range' => [
                'description' => 'Preset comparison date range.',
            ],
            'compare_date_from' => [
                'description' => 'Start of a custom comparison period.',
            ],
            'compare_date_to' => [
                'description' => 'End of a custom comparison period.',
            ],
            'format' => [
                'description' => 'Registered output formatter.',
                'example' => 'chartjs',
            ],
            'filters' => [
                'description' => 'Model-specific filters to apply to the stat.',
            ],
        ];
    }
}
