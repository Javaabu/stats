<?php

namespace Javaabu\Stats\Formatters\Categorical;

use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Enums\CategoricalModes;

class DefaultCategoricalStatsFormatter extends AbstractCategoricalStatsFormatter
{
    /**
     * @param  list<int|string>  $categorical_values
     * @return array{stats: list<array<string, int|float|string>>, compare: list<array<string, int|float|string>>|null}
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

        return [
            'stats' => $this->resultsToArray($stats, $category_names, $stats->resultsToArray($stats_results)),
            'compare' => $compare_results
                ? $this->resultsToArray($stats, $category_names, $stats->resultsToArray($compare_results))
                : null,
        ];
    }

    /**
     * @param  array<int|string, string>  $category_names
     * @param  array<int|string, int|float>  $results
     * @return list<array<string, int|float|string>>
     */
    protected function resultsToArray(CategoricalStatsRepository $stats, array $category_names, array $results): array
    {
        $output = [];

        foreach ($category_names as $id => $label) {
            $output[] = [
                $stats->getCategoryFieldAlias() => $id,
                $stats->getCategoryNameFieldAlias() => $label,
                $stats->getAggregateFieldName() => $results[$id] ?? 0,
            ];
        }

        return $output;
    }
}
