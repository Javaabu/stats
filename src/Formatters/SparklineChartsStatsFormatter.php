<?php
/**
 * Chartjs stats formatter
 */

namespace Javaabu\Stats\Formatters;

use Javaabu\Stats\StatsRepository;
use Illuminate\Support\Str;

class SparklineChartsStatsFormatter extends StatsFormatter
{
    /**
     * Format the data
     *
     * @param string $mode
     * @return array
     */
    public function format($mode)
    {
        $stats_repo = $this->getStats();

        $stats_interval = $stats_repo->interval($mode);
        $max_interval = $stats_interval;

        $field_name = $stats_repo->getAggregateFieldName();

        $stats = $stats_repo->results($mode)->pluck($field_name, $mode);

        // loop over all
        $stat_next_date = $stats_repo->getDateFrom()->copy();

        $stats_data = [];
        $increment_method = Str::camel('add_'.$mode);

        for ($i = 0; $i < $max_interval; $i++) {
            // get the stat
            $stat_date_str = StatsRepository::formatDateForMode($stat_next_date, $mode, false);
            $stat = $stats->get($stat_date_str);
            $stats_data[] = $stat ? $stat + 0 : 0;
            $stat_next_date = $stat_next_date->{$increment_method}();
        }

        return $stats_data;
    }
}
