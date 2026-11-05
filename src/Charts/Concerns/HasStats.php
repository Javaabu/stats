<?php

namespace Javaabu\Stats\Charts\Concerns;

use Illuminate\Support\Str;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\PresetDateRanges;

/**
 * The stats a chart plots: one or more registered metrics, each with an
 * optional label and filters of its own
 */
trait HasStats
{
    /** @var list<array{metric: string, label: string|null, filters: array<string, mixed>}> */
    protected array $series = [];

    /**
     * Add a stat to the chart
     *
     * @param  array<string, mixed>  $filters  filters for this stat only
     */
    public function stat(string $metric, ?string $label = null, array $filters = []): static
    {
        $this->series[] = ['metric' => $metric, 'label' => $label, 'filters' => $filters];

        $this->computed = null;

        return $this;
    }

    /**
     * Add several stats: a list of metrics, or labels keyed by metric
     *
     * @param  array<int|string, string>  $metrics
     */
    public function stats(array $metrics): static
    {
        foreach ($metrics as $key => $value) {
            is_int($key) ? $this->stat($value) : $this->stat($key, $value);
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function metrics(): array
    {
        return array_values(array_unique(array_column($this->series, 'metric')));
    }

    /**
     * @return list<array{metric: string, label: string|null, filters: array<string, mixed>}>
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    /**
     * Data keys for the series: CSS-safe and unique (`payments_count`, `payments_count_2`)
     *
     * @return list<string>
     */
    protected function seriesKeys(): array
    {
        $keys = [];

        foreach ($this->series as $series) {
            $key = Str::of(class_basename($series['metric']))->snake()->slug('_')->toString() ?: 'stat';
            $base = $key;
            $i = 2;

            while (in_array($key, $keys, true) || in_array($key, ['date', 'category', 'previous'], true)) {
                $key = $base . '_' . $i++;
            }

            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * The chart's fixed filters merged with a series' own
     *
     * @param  array{metric: string, label: string|null, filters: array<string, mixed>}  $series
     * @return array<string, mixed>
     */
    protected function filtersFor(array $series): array
    {
        return array_merge($this->filters, $series['filters']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function seriesCacheKey(): array
    {
        return [
            'series' => $this->series,
            'filters' => $this->filters,
        ];
    }

    /**
     * The dates a range resolves to now, for cache keys. Presets are keyed by
     * day, so a range that ends "now" (lifetime) keeps its key all day; the
     * TTL decides how fresh it is.
     *
     * @return array{name: string, from: string, to: string}
     */
    protected static function rangeKey(DateRange $range): array
    {
        $format = $range instanceof PresetDateRanges ? 'Y-m-d' : 'Y-m-d H:i:s';

        return [
            'name' => $range->getName(),
            'from' => $range->getDateFrom()->format($format),
            'to' => $range->getDateTo()->format($format),
        ];
    }
}
