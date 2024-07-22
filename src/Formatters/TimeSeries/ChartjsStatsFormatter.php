<?php
/**
 * Chartjs stats formatter
 */

namespace Javaabu\Stats\Formatters\TimeSeries;

use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;

class ChartjsStatsFormatter extends AbstractTimeSeriesStatsFormatter
{
    /**
     * Format the data
     */
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array
    {
        $stats_repo = $stats;
        $compare_repo = $compare;

        $stats_interval = $stats_repo->interval($mode);

        $compare_interval = $compare_repo ? $compare_repo->interval($mode) : 0;
        $max_interval = max($stats_interval, $compare_interval);

        $field_name = $stats_repo->getAggregateFieldName();

        $stats = $stats_repo->results($mode)->pluck($field_name, $mode->value);
        $compare = $compare_repo ? $compare_repo->results($mode)->pluck($field_name, $mode->value) : null;

        $labels = [];

        // loop over all
        $stat_next_date = $stats_repo->getDateFrom()->copy();
        $compare_next_date = $compare_repo ? $compare_repo->getDateFrom()->copy() : null;

        $stats_data = [];
        $compare_data = $compare ? [] : null;

        for ($i = 0; $i <= $max_interval; $i++) {
            // get the stat
            $stat_date_str = $mode->formatDate($stat_next_date, false);
            $stat = $stats->get($stat_date_str);
            $stats_data[] = $stat ? $stat + 0 : 0;
            $label = $stat_next_date ? $mode->formatDate($stat_next_date) : '';
            $stat_next_date = $mode->increment($stat_next_date);

            if ($compare_next_date) {
                $compare_date_str = $mode->formatDate($compare_next_date, false);
                $comparison = $compare ? $compare->get($compare_date_str) : null;
                $compare_data[] = $comparison ? $comparison + 0 : 0;
                $label .= '#'.$mode->formatDate($compare_next_date);
                $compare_next_date = $mode->increment($compare_next_date);
            }

            $labels[] = $label;
        }

        $stats = $stats_data;
        $compare = $compare_data;

        return compact('labels', 'stats', 'compare');
    }
}
