<?php

namespace Javaabu\Stats\Concerns;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Stats\Cache\StatsCache;

/**
 * For models that stats are computed from: invalidates the cached stats
 * when a model is created, updated, deleted or restored.
 *
 * ```php
 * class Payment extends Model
 * {
 *     use InvalidatesStats;
 *
 *     // optional: only these metrics; default is every metric
 *     protected array $invalidates_stats = ['payments_count', 'payments_by_status'];
 * }
 * ```
 *
 * @mixin Model
 */
trait InvalidatesStats
{
    public static function bootInvalidatesStats(): void
    {
        $forget = fn (Model $model) => app(StatsCache::class)->forget($model->statsToInvalidate());

        static::saved($forget);
        static::deleted($forget);

        if (method_exists(static::class, 'restored')) {
            static::restored($forget);
        }
    }

    /**
     * The metrics whose cached results this model affects
     *
     * @return list<string>
     */
    public function statsToInvalidate(): array
    {
        return property_exists($this, 'invalidates_stats') ? $this->invalidates_stats : [StatsCache::ALL];
    }
}
