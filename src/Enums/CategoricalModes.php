<?php

namespace Javaabu\Stats\Enums;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Javaabu\Helpers\Enums\IsEnum;
use Javaabu\Helpers\Enums\NativeEnumsTrait;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;

enum CategoricalModes: string implements IsEnum
{
    use NativeEnumsTrait;

    case NON_EMPTY = 'non_empty';
    case ALL = 'all';
    case SPECIFIC = 'specific';

    /**
     * @param  Collection<int, Model>  $stats_results
     * @param  Collection<int, Model>|null  $compare_results
     * @param  list<int|string>  $categorical_values
     * @return array<int|string, string>
     */
    public function resolveCategoryNames(
        CategoricalStatsRepository $stats,
        Collection $stats_results,
        ?Collection $compare_results = null,
        array $categorical_values = []
    ): array {
        if ($this == self::NON_EMPTY) {
            $categorical_values = array_unique(array_merge(
                $stats_results->pluck($stats->getCategoryFieldAlias())->all(),
                $compare_results?->pluck($stats->getCategoryFieldAlias())->all() ?? []
            ));
        }

        return $stats->resolveCategoryNames($this, $categorical_values);
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NON_EMPTY => 'Returns categories with records in the selected primary or comparison period.',
            self::ALL => 'Returns every item supplied by the category provider.',
            self::SPECIFIC => 'Returns only the ids supplied through categorical_values.',
        };
    }
}
