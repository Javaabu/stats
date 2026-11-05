<?php

namespace Javaabu\Stats\Facades;

use Illuminate\Support\Facades\Facade;
use Javaabu\Stats\StatsManager;

/**
 * @method static \Javaabu\Stats\Charts\TimeSeriesChart timeSeries(?string $metric = null, ?string $label = null)
 * @method static \Javaabu\Stats\Charts\CategoryChart category(?string $metric = null, ?string $label = null)
 * @method static \Javaabu\Stats\Charts\PieChart pie(?string $metric = null, ?string $label = null)
 * @method static \Javaabu\Stats\Charts\Sparkline sparkline(?string $metric = null, ?string $label = null)
 * @method static \Javaabu\Stats\Charts\StatTile tile(?string $metric = null, ?string $label = null)
 * @method static \Javaabu\Stats\Cache\StatsCache cache()
 * @method static void forget(string|array $metrics = '*')
 * @method static void flush()
 * @method static StatsManager warm(\Javaabu\Stats\Charts\Chart|iterable|\Closure $charts)
 * @method static array warmables()
 * @method static void clearWarmables()
 * @method static int warmNow(?\Closure $after = null)
 *
 * @see StatsManager
 */
class Stats extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StatsManager::class;
    }
}
