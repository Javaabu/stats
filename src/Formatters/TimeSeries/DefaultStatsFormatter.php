<?php
/**
 * Default stats formatter
 */

namespace Javaabu\Stats\Formatters\TimeSeries;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;

class DefaultStatsFormatter extends AbstractTimeSeriesStatsFormatter
{
    /**
     * Format the data
     */
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $stats = $stats->results($mode);
        $compare = $compare?->results($mode);

        return compact('stats', 'compare');
    }
}
