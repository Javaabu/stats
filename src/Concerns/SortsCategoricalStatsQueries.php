<?php

namespace Javaabu\Stats\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @template TModel of Model
 */
trait SortsCategoricalStatsQueries
{
    protected ?string $categorical_stats_sort_field = null;

    protected string $categorical_stats_sort_direction = 'asc';

    /** @var (Closure(Builder<TModel>): mixed)|null */
    protected ?Closure $categorical_stats_sort_query = null;

    public function sortCategoricalStatsItemsBy(string $field, string $direction = 'asc'): static
    {
        $this->categorical_stats_sort_field = $field;
        $this->categorical_stats_sort_direction = $this->validateCategoricalStatsSortDirection($direction);
        $this->categorical_stats_sort_query = null;

        return $this;
    }

    /**
     * @param  Closure(Builder<TModel>): mixed  $callback
     */
    public function sortCategoricalStatsItemsUsing(Closure $callback): static
    {
        $this->categorical_stats_sort_query = $callback;
        $this->categorical_stats_sort_field = null;

        return $this;
    }

    /**
     * @param  Builder<TModel>  $query
     */
    protected function applyCategoricalStatsSort(Builder $query, string $default_sort_field): void
    {
        if ($this->categorical_stats_sort_query) {
            ($this->categorical_stats_sort_query)($query);
        } elseif ($this->categorical_stats_sort_field) {
            $query->reorder($this->categorical_stats_sort_field, $this->categorical_stats_sort_direction);
        } elseif (empty($query->getQuery()->orders)) {
            $query->orderBy($default_sort_field);
        }
    }

    protected function validateCategoricalStatsSortDirection(string $direction): string
    {
        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Invalid categorical stats sort direction [{$direction}].");
        }

        return $direction;
    }
}
