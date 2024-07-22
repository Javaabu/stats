<?php
/**
 * Sparkline stats formatter
 */

namespace Javaabu\Stats\Formatters\TimeSeries;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;

class SparklineChartsStatsFormatter extends AbstractTimeSeriesStatsFormatter
{
    /**
     * Format the data
     */
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $stats_repo = $stats;

        $stats_interval = $stats_repo->interval($mode);
        $max_interval = $stats_interval;

        $field_name = $stats_repo->getAggregateFieldName();

        $stats = $stats_repo->results($mode)->pluck($field_name, $mode->value);

        // loop over all
        $stat_next_date = $stats_repo->getDateFrom()->copy();

        $stats_data = [];

        for ($i = 0; $i <= $max_interval; $i++) {
            // get the stat
            $stat_date_str = $mode->formatDate($stat_next_date, false);
            $stat = $stats->get($stat_date_str);
            $stats_data[] = $stat ? $stat + 0 : 0;
            $stat_next_date = $mode->increment($stat_next_date);
        }

        return $stats_data;
    }
}
