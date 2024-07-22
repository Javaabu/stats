<?php
/**
 * Stats formatters base class
 */

namespace Javaabu\Stats\Formatters;

use Javaabu\Stats\Formatters\TimeSeries\ChartjsStatsFormatter;
use Javaabu\Stats\StatsRepository;

abstract class StatsFormatter
{
    /**
     * @var string[]
     */
    const FORMATTERS = [
        'default' => DefaultStatsFormatter::class,
        'chartjs' => ChartjsStatsFormatter::class,
        'sparkline' => SparklineChartsStatsFormatter::class,
        'flot' => FlotStatsFormatter::class,
        'combined' => CombinedStatsFormatter::class,
    ];

    /**
     * @var StatsRepository
     */
    protected $stats;

    /**
     * @var StatsRepository
     */
    protected $compare;

    /**
     * Create a new stats formatter instance.
     *
     * @param StatsRepository $stats
     * @param StatsRepository|null $compare
     */
    public function __construct(StatsRepository $stats, StatsRepository $compare = null)
    {
        $this->stats = $stats;
        $this->compare = $compare;
    }

    /**
     * Create from formatter
     */
    public static function createFromFormat(string $format): StatsFormatter
    {
        $class = self::getFormatterClass($format);
        return new $class();
    }

    /**
     * Get the formatter class
     *
     * @param $formatter
     * @return string
     */
    public static function getFormatterClass($formatter)
    {
        if (! array_key_exists($formatter, self::FORMATTERS)) {
            throw new \InvalidArgumentException('Invalid formatter');
        }

        return self::FORMATTERS[$formatter];
    }

    /**
     * Get the modes
     *
     * @return string[]
     */
    public static function getFormatters()
    {
        return self::FORMATTERS;
    }

    /**
     * Get the stats
     *
     * @return StatsRepository
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Get the comparison data
     *
     * @return StatsRepository
     */
    public function getCompare()
    {
        return $this->compare;
    }

    /**
     * Format the data
     *
     * @param string $mode
     * @return array
     */
    public abstract function format($mode);
}
