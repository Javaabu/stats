<?php
/**
 * Combined stats formatter
 */

namespace Javaabu\Stats\Formatters\TimeSeries;

use Illuminate\Support\Str;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\StatsRepository;

class CombinedStatsFormatter extends AbstractTimeSeriesStatsFormatter
{
    /**
     * Format the data
     */
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $stats_repo = $stats;
        $compare_repo = $compare;

        $stats_interval = $stats_repo->interval($mode);
        //dd($stats_interval);
        $compare_interval = $compare_repo ? $compare_repo->interval($mode) : 0;
        $max_interval = max($stats_interval, $compare_interval);

        $field_name = $stats_repo->getAggregateFieldName();

        $stats = $stats_repo->results($mode)->pluck($field_name, $mode->value);
        $compare = $compare_repo ? $compare_repo->results($mode)->pluck($field_name, $mode->value) : null;

        $stat_date_range = $stats_repo->formattedDateRange();
        $compare_date_range = $compare_repo ? $compare_repo->formattedDateRange() : '';

        $stat_next_date = $stats_repo->getDateFrom()->copy();
        $compare_next_date = $compare_repo ? $compare_repo->getDateFrom()->copy() : null;

        $data = [];

        for ($i = 0; $i <= $max_interval; $i++) {
            // get the stat
            $stats_data = [];
            $compare_data = [];

            $stat_date_str = $mode->formatDate($stat_next_date, false);
            $stat = $stats->get($stat_date_str);
            $stats_data[$field_name] = $stat ? $stat + 0 : 0;
            $stats_data[$mode->value] = $stat_date_str;
            $stat_next_date = $mode->increment($stat_next_date);

            if ($compare_next_date) {
                $stats_data['date_range'] = $stat_date_range;

                $compare_date_str = $mode->formatDate($compare_next_date, false);
                $comparison = $compare ? $compare->get($compare_date_str) : null;
                $compare_data[$field_name] = $comparison ? $comparison + 0 : 0;
                $compare_data[$mode->value] = $compare_date_str;
                $compare_data['date_range'] = $compare_date_range;
                $compare_next_date = $mode->increment($compare_next_date);
            }

            $data[] = $stats_data;

            if ($compare_data) {
                $data[] = $compare_data;
            }
        }

        return $data;
    }
}
