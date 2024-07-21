<?php
/**
 * Stats formatters base class
 */

namespace Javaabu\Stats\Formatters\TimeSeries;

use Javaabu\Stats\Contracts\TimeSeriesStatsFormatter;
use Javaabu\Stats\TimeSeriesStats;

abstract class AbstractTimeSeriesStatsFormatter implements TimeSeriesStatsFormatter
{
    public function getName(): string
    {
        return TimeSeriesStats::getNameForFormatter(get_class($this));
    }
}
