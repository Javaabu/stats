<?php
/**
 * Combined stats formatter
 */

namespace Javaabu\Stats\Formatters;

use Javaabu\Stats\StatsRepository;
use Illuminate\Support\Str;

class CombinedStatsFormatter extends StatsFormatter
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

        $stat_date_range = $stats_repo->formattedDateRange();
        $compare_date_range = $compare_repo ? $compare_repo->formattedDateRange() : '';

        $stat_next_date = $stats_repo->getDateFrom()->copy();
        $compare_next_date = $compare_repo ? $compare_repo->getDateFrom()->copy() : null;

        $data = [];
        $increment_method = Str::camel('add_'.$mode);

        for ($i = 0; $i < $max_interval; $i++) {
            // get the stat
            $stats_data = [];
            $compare_data = [];

            $stat_date_str = StatsRepository::formatDateForMode($stat_next_date, $mode, false);
            $stat = $stats->get($stat_date_str);
            $stats_data[$field_name] = $stat ? $stat + 0 : 0;
            $stats_data[$mode] = $stat_date_str;
            $stat_next_date = $stat_next_date->{$increment_method}();

            if ($compare_next_date) {
                $stats_data['date_range'] = $stat_date_range;

                $compare_date_str = StatsRepository::formatDateForMode($compare_next_date, $mode, false);
                $comparison = $compare ? $compare->get($compare_date_str) : null;
                $compare_data[$field_name] = $comparison ? $comparison + 0 : 0;
                $compare_data[$mode] = $compare_date_str;
                $compare_data['date_range'] = $compare_date_range;
                $compare_next_date = $compare_next_date->{$increment_method}();
            }

            $data[] = $stats_data;

            if ($compare_data) {
                $data[] = $compare_data;
            }
        }

        return $data;
    }
}
