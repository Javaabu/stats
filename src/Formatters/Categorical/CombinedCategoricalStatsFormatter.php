<?php

namespace Javaabu\Stats\Formatters\Categorical;

use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Enums\CategoricalModes;

class CombinedCategoricalStatsFormatter extends AbstractCategoricalStatsFormatter
{
    /**
     * @param  list<int|string>  $categorical_values
     * @return list<array<string, int|float|string|null>>
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
        $output = [];

        foreach ($category_names as $id => $label) {
            $output[] = [
                $stats->getCategoryFieldAlias() => $id,
                $stats->getCategoryNameFieldAlias() => $label,
                $stats->getAggregateFieldName() => $stats_values[$id] ?? 0,
                'compare_'.$stats->getAggregateFieldName() => $compare ? ($compare_values[$id] ?? 0) : null,
            ];
        }

        return $output;
    }
}
