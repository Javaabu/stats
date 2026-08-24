<?php

namespace Javaabu\Stats\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Javaabu\Stats\Enums\CategoricalModes;
use UnitEnum;

interface CategoricalStatsRepository extends InteractsWithDateRange, InteractsWithFilters
{
    /**
     * Get the base query.
     *
     * @return Builder<Model>
     */
    public function query(): Builder;

    /**
     * Get the category provider source.
     *
     * @return CategoryProvider|Builder<Model>|Model|Collection<array-key, mixed>|array<array-key, mixed>|class-string<CategoryProvider|Model|UnitEnum>
     */
    public function categoryProvider(): mixed;

    /**
     * Get the normalized category provider.
     */
    public function getCategoryProvider(): CategoryProvider;

    /**
     * Get the database field used to group categories.
     */
    public function getCategoryField(): string;

    /**
     * Get the result field used for the grouped category id.
     */
    public function getCategoryFieldAlias(): string;

    /**
     * Get the result field used for the category name.
     */
    public function getCategoryNameFieldAlias(): string;

    /**
     * Get the category column title.
     */
    public function getCategoryTitle(): string;

    /**
     * Get the category ID column title.
     */
    public function getCategoryIdTitle(): string;

    /**
     * Get the date field used to constrain the stat.
     */
    public function getDateField(): string;

    /**
     * Get the aggregate field name.
     */
    public function getAggregateFieldName(): string;

    /**
     * Get the aggregate field label.
     */
    public function getAggregateFieldLabel(): string;

    /**
     * Get the stat name.
     */
    public function getName(): string;

    /**
     * Check whether the given user can view the stat.
     */
    public function canView(?Authorizable $user = null): bool;

    /**
     * Get the registered metric.
     */
    public function metric(): string;

    /**
     * Get grouped categorical results.
     *
     * @return Collection<int, Model>
     */
    public function results(): Collection;

    /**
     * Convert grouped results to values keyed by category id.
     *
     * @param  Collection<int, Model>  $results
     * @return array<int|string, int|float>
     */
    public function resultsToArray(Collection $results): array;

    /**
     * Resolve labels for the requested categories.
     *
     * @param  list<int|string>  $categorical_values
     * @return array<int|string, string>
     */
    public function resolveCategoryNames(CategoricalModes $mode, array $categorical_values = []): array;

    /**
     * Format the result.
     *
     * @param  list<int|string>  $categorical_values
     * @return array<array-key, mixed>
     */
    public function format(
        string $format,
        ?CategoricalStatsRepository $compare = null,
        CategoricalModes $mode = CategoricalModes::NON_EMPTY,
        array $categorical_values = []
    ): array;
}
