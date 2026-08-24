<?php

namespace Javaabu\Stats\Contracts;

use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CategoryProvider
{
    /**
     * Get the category column title.
     */
    public function getCategoricalStatsCategoryTitle(): string;

    /**
     * Get the category ID column title.
     */
    public function getCategoricalStatsCategoryIdTitle(): string;

    /**
     * Sort category items by a field.
     */
    public function sortCategoricalStatsItemsBy(string $field, string $direction = 'asc'): static;

    /**
     * Sort category items using a custom callback.
     */
    public function sortCategoricalStatsItemsUsing(Closure $callback): static;

    /**
     * Get category items, optionally limited to the given ids.
     *
     * @param  list<int|string>|null  $ids
     * @return Collection<int, array{id: int|string, label: string}>
     */
    public function getCategoricalStatsItems(?array $ids = null): Collection;

    /**
     * Search category items.
     *
     * @return LengthAwarePaginator<int, array{id: int|string, label: string}>
     */
    public function searchCategoricalStatsItems(?string $search = null, int $per_page = 15): LengthAwarePaginator;
}
