<?php

namespace Javaabu\Stats\Cache;

use BackedEnum;
use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Caches stat results under keys that include a version per metric, so a
 * metric (or every metric) is invalidated by bumping its version, on any
 * cache store and without tags.
 *
 * TTLs: results of periods that ended before today don't change, so they
 * keep for `stats.cache.ttl.past` seconds; anything that includes today keeps
 * for `stats.cache.ttl.current` seconds.
 */
class StatsCache
{
    /** The metric name that stands for every metric */
    public const string ALL = '*';

    public function __construct(protected CacheFactory $cache) {}

    public function enabled(): bool
    {
        return (bool) config('stats.cache.enabled', true);
    }

    public function store(): Repository
    {
        return $this->cache->store(config('stats.cache.store'));
    }

    public function prefix(): string
    {
        return (string) config('stats.cache.prefix', 'javaabu_stats');
    }

    /**
     * Get the value from the cache, or compute and store it
     *
     * @template TValue
     *
     * @param  array<string, mixed>  $key  what the value depends on
     * @param  list<string>  $metrics  the metrics it's computed from, for invalidation
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(array $key, array $metrics, Closure $callback, ?int $ttl = null, ?CarbonInterface $ends_at = null): mixed
    {
        if (! $this->enabled() || $ttl === 0) {
            return $callback();
        }

        return $this->store()->remember(
            $this->key($key, $metrics),
            $ttl ?? $this->ttlFor($ends_at),
            $callback,
        );
    }

    /**
     * Compute the value and store it, whatever is in the cache
     *
     * @template TValue
     *
     * @param  array<string, mixed>  $key
     * @param  list<string>  $metrics
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function refresh(array $key, array $metrics, Closure $callback, ?int $ttl = null, ?CarbonInterface $ends_at = null): mixed
    {
        $value = $callback();

        if ($this->enabled() && $ttl !== 0) {
            $this->store()->put($this->key($key, $metrics), $value, $ttl ?? $this->ttlFor($ends_at));
        }

        return $value;
    }

    /**
     * Whether a value is cached
     *
     * @param  array<string, mixed>  $key
     * @param  list<string>  $metrics
     */
    public function has(array $key, array $metrics): bool
    {
        return $this->store()->has($this->key($key, $metrics));
    }

    /**
     * The seconds to keep a result whose period ends at the given time
     */
    public function ttlFor(?CarbonInterface $ends_at = null): int
    {
        if ($ends_at && $ends_at->lt(now()->startOfDay())) {
            return (int) config('stats.cache.ttl.past', 60 * 60 * 24);
        }

        return (int) config('stats.cache.ttl.current', 60 * 10);
    }

    /**
     * Invalidate what's cached for the metrics (all metrics for `*` or none given)
     *
     * @param  string|list<string>  $metrics
     */
    public function forget(string|array $metrics = self::ALL): void
    {
        $metrics = (array) $metrics;

        if ($metrics === [] || in_array(self::ALL, $metrics, true)) {
            $this->flush();

            return;
        }

        foreach (array_unique($metrics) as $metric) {
            $this->store()->forever($this->versionKey($metric), $this->newVersion());
        }
    }

    /**
     * Invalidate everything the package cached
     */
    public function flush(): void
    {
        $this->store()->forever($this->versionKey(self::ALL), $this->newVersion());
    }

    /**
     * The cache key for a value
     *
     * @param  array<string, mixed>  $key
     * @param  list<string>  $metrics
     */
    public function key(array $key, array $metrics): string
    {
        $metrics = array_values(array_unique($metrics));
        sort($metrics);

        $versions = [$this->version(self::ALL)];

        foreach ($metrics as $metric) {
            $versions[] = $this->version($metric);
        }

        $hash = md5(json_encode([$this->normalize($key), $metrics, $versions], JSON_THROW_ON_ERROR));

        return $this->prefix() . ':' . $hash;
    }

    /**
     * The current version of a metric's results
     */
    public function version(string $metric): string
    {
        return (string) ($this->store()->get($this->versionKey($metric)) ?? '0');
    }

    protected function versionKey(string $metric): string
    {
        return $this->prefix() . ':version:' . ($metric === self::ALL ? '*' : md5($metric));
    }

    protected function newVersion(): string
    {
        return Str::random(12);
    }

    /**
     * Dates, enums and objects as stable scalars
     */
    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            ksort($value);

            return array_map(fn ($item) => $this->normalize($item), $value);
        }

        if (is_object($value)) {
            return get_class($value) . ':' . md5(serialize($value));
        }

        return $value;
    }
}
