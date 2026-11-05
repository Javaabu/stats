<?php

namespace Javaabu\Stats\Charts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Javaabu\Stats\Cache\StatsCache;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\TimeSeriesStats;
use Javaabu\Stats\Views\Components\Chart as ChartComponent;
use JsonSerializable;

/**
 * A chart of one or more stats, described in PHP and drawn by Onu's chart
 * components in React (`<StatsChart chart={…} />`) or as SVG in Blade
 * (`<x-stats::chart :chart="$chart" />`).
 *
 * The data is computed when the chart is serialised, and cached (see
 * {@see StatsCache}) unless caching is turned off for the chart.
 *
 * @implements Arrayable<string, mixed>
 */
abstract class Chart implements Arrayable, Htmlable, JsonSerializable
{
    public const string FORMAT_NUMBER = 'number';

    public const string FORMAT_INTEGER = 'integer';

    public const string FORMAT_COMPACT = 'compact';

    public const string FORMAT_PERCENT = 'percent';

    public const string FORMAT_CURRENCY = 'currency';

    protected ?string $title = null;

    protected ?string $description = null;

    protected ?string $id = null;

    protected ?DateRange $range = null;

    /** @var array<string, mixed> */
    protected array $filters = [];

    protected string $value_format = self::FORMAT_NUMBER;

    protected ?int $height = null;

    /** Seconds to cache the data: null for the default, 0 for none */
    protected ?int $cache_ttl = null;

    protected bool $cache_enabled = true;

    /** @var array<string, mixed>|null */
    protected ?array $computed = null;

    /**
     * The chart's type, as the React and Blade renderers know it
     */
    abstract public function type(): string;

    /**
     * The metrics the chart is computed from
     *
     * @return list<string>
     */
    abstract public function metrics(): array;

    /**
     * Compute the chart's data
     *
     * @return array<string, mixed>
     */
    abstract protected function compute(): array;

    /**
     * What the cached data depends on, besides the metrics
     *
     * @return array<string, mixed>
     */
    abstract protected function cacheKey(): array;

    /**
     * The latest moment the chart's data covers, which decides its TTL
     */
    abstract protected function endsAt(): ?CarbonInterface;

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? $this->defaultTitle();
    }

    /**
     * The title when none is given: the name of the first stat
     */
    abstract protected function defaultTitle(): string;

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id ??= 'stats-chart-' . substr(md5(static::class . json_encode($this->cacheKey()) . implode(',', $this->metrics())), 0, 8);
    }

    /**
     * The date range of the chart. Default: `config('stats.default_date_range')`
     */
    public function range(DateRange|string $range): static
    {
        $this->range = is_string($range) ? PresetDateRanges::from($range) : $range;

        $this->computed = null;

        return $this;
    }

    public function getRange(): DateRange
    {
        return $this->range ?? TimeSeriesStats::defaultDateRange();
    }

    /**
     * Fixed filters for every stat of the chart
     *
     * @param  array<string, mixed>  $filters
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        $this->computed = null;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * How values are formatted: number, integer, compact, percent or currency (MVR)
     */
    public function valueFormat(string $format): static
    {
        $this->value_format = $format;

        return $this;
    }

    public function getValueFormat(): string
    {
        return $this->value_format;
    }

    public function height(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * Cache the data for the given seconds, or `false` not to cache it
     */
    public function cache(int|bool $seconds = true, ?int $minutes = null): static
    {
        if ($minutes !== null) {
            $seconds = $minutes * 60;
        }

        $this->cache_enabled = $seconds !== false && $seconds !== 0;
        $this->cache_ttl = is_int($seconds) && $seconds > 0 ? $seconds : null;

        return $this;
    }

    public function withoutCache(): static
    {
        return $this->cache(false);
    }

    public function isCached(): bool
    {
        return $this->cache_enabled;
    }

    /**
     * The chart's data, from the cache when it's there
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        if ($this->computed !== null) {
            return $this->computed;
        }

        if (! $this->cache_enabled) {
            return $this->computed = $this->compute();
        }

        return $this->computed = $this->statsCache()->remember(
            $this->fullCacheKey(),
            $this->metrics(),
            fn () => $this->compute(),
            $this->cache_ttl,
            $this->endsAt(),
        );
    }

    /**
     * Compute the data again and put it in the cache
     *
     * @return array<string, mixed>
     */
    public function refresh(): array
    {
        if (! $this->cache_enabled) {
            return $this->computed = $this->compute();
        }

        return $this->computed = $this->statsCache()->refresh(
            $this->fullCacheKey(),
            $this->metrics(),
            fn () => $this->compute(),
            $this->cache_ttl,
            $this->endsAt(),
        );
    }

    /**
     * Whether the chart's data is in the cache
     */
    public function isWarm(): bool
    {
        return $this->cache_enabled && $this->statsCache()->has($this->fullCacheKey(), $this->metrics());
    }

    /**
     * @return array<string, mixed>
     */
    protected function fullCacheKey(): array
    {
        return [
            'type' => $this->type(),
            'chart' => static::class,
            'locale' => app()->getLocale(),
            ...$this->cacheKey(),
        ];
    }

    protected function statsCache(): StatsCache
    {
        return app(StatsCache::class);
    }

    /**
     * The chart as the props of the React `<StatsChart>` and the Blade components
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->description,
            'valueFormat' => $this->value_format,
            'height' => $this->height,
            ...$this->data(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * The chart as SVG, through the `<x-stats::chart>` Blade component
     */
    public function render(): string
    {
        return Blade::renderComponent(new ChartComponent($this));
    }

    public function toHtml(): string
    {
        return $this->render();
    }
}
