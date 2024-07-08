<?php
/**
 * Chartjs stats formatter
 */

namespace Javaabu\Stats\Formatters;

use Javaabu\Stats\StatsRepository;
use Illuminate\Support\Str;

class ChartjsStatsFormatter extends StatsFormatter
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
        $compare_repo = $this->getCompare();

        $stats_interval = $stats_repo->interval($mode);
        //dd($stats_interval);
        $compare_interval = $compare_repo ? $compare_repo->interval($mode) : 0;
        $max_interval = max($stats_interval, $compare_interval);

        $field_name = $stats_repo->getAggregateFieldName();

        $stats = $stats_repo->results($mode)->pluck($field_name, $mode);
        $compare = $compare_repo ? $compare_repo->results($mode)->pluck($field_name, $mode) : null;

        $labels = [];

        // loop over all
        $stat_next_date = $stats_repo->getDateFrom()->copy();
        $compare_next_date = $compare_repo ? $compare_repo->getDateFrom()->copy() : null;

        $stats_data = [];
        $compare_data = $compare ? [] : null;
        $increment_method = Str::camel('add_'.$mode);

        for ($i = 0; $i < $max_interval; $i++) {
            // get the stat
            $stat_date_str = StatsRepository::formatDateForMode($stat_next_date, $mode, false);
            $stat = $stats->get($stat_date_str);
            $stats_data[] = $stat ? $stat + 0 : 0;
            $label = $stat_next_date ? StatsRepository::formatDateForMode($stat_next_date, $mode) : '';
            $stat_next_date = $stat_next_date->{$increment_method}();

            if ($compare_next_date) {
                $compare_date_str = StatsRepository::formatDateForMode($compare_next_date, $mode, false);
                $comparison = $compare ? $compare->get($compare_date_str) : null;
                $compare_data[] = $comparison ? $comparison + 0 : 0;
                $label .= '#'.StatsRepository::formatDateForMode($compare_next_date, $mode);
                $compare_next_date = $compare_next_date->{$increment_method}();
            }

            $labels[] = $label;
        }

        $stats = $stats_data;
        $compare = $compare_data;

        return compact('labels', 'stats', 'compare');
    }
}
