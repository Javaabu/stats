<?php

namespace Javaabu\Stats\Formatters\Categorical;

use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Enums\CategoricalModes;

class ChartjsCategoricalStatsFormatter extends AbstractCategoricalStatsFormatter
{
    /**
     * @param  list<int|string>  $categorical_values
     * @return array{labels: list<string>, stats: list<int|float>, compare: list<int|float>|null}
     */
    public function format(
        CategoricalStatsRepository $stats,
        ?CategoricalStatsRepository $compare = null,
        CategoricalModes $mode = CategoricalModes::NON_EMPTY,
        array $categorical_values = []
    ): array {
        $stats_results = $stats->results();
        $compare_results = $compare?->results();
        $category_names = $mode->resolveCategoryNames($stats, $stats_results, $compare_results, $categorical_values);
        $stats_values = $stats->resultsToArray($stats_results);
        $compare_values = $compare_results ? $stats->resultsToArray($compare_results) : null;
        $chart_stats = [];
        $chart_compare = $compare ? [] : null;

        foreach (array_keys($category_names) as $id) {
            $chart_stats[] = $stats_values[$id] ?? 0;

            if ($compare) {
                $chart_compare[] = $compare_values[$id] ?? 0;
            }
        }

        return [
            'labels' => array_values($category_names),
            'stats' => $chart_stats,
            'compare' => $chart_compare,
        ];
    }
}
