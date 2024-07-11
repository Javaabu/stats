<?php

namespace Javaabu\Stats\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\Filter;
use Javaabu\Stats\Contracts\InteractsWithDateRange;
use Javaabu\Stats\Exceptions\InvalidFiltersException;

/** @var $this InteractsWithDateRange */
trait HasFilters
{
    protected array $filters;

    /**
     * Get the filters
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get a specific filter
     */
    public function  getFilter(string $filter, $default = null)
    {
        return $this->filters[$filter] ?? $default;
    }

    /**
     * Get the filtered query
     */
    public function filteredQuery(): Builder
    {
        return $this->applyDateFilters($this->applyFilters($this->query()));
    }

    /**
     * Check if all the filters are allowed
     */
    public function ensureAllFiltersAllowed(array $filters)
    {
        $filter_names = collect(array_keys($filters));

        $allowed_filter_names = collect($this->allowedFilters())->map(function (Filter $filter) {
            return $filter->getName();
        });

        $diff = $filter_names->diff($allowed_filter_names);

        if ($diff->count()) {
            throw InvalidFiltersException::filtersNotAllowed($diff, $allowed_filter_names);
        }

        $this->filters = $filters;
    }

    /**
     * Get all the allowed filters
     */
    public function allowedFilters(): array
    {
        return property_exists($this, 'allowed_filters') ? $this->allowed_filters : [];
    }

    /**
     * Apply the filters
     */
    public function applyFilters(Builder $query): Builder
    {
        collect($this->allowedFilters())
            ->each(function (Filter $filter) use ($query) {
                if ($value = $this->getFilter($filter->getName())) {
                    $filter->apply($query, $value, $this);
                }
            });

        return $query;
    }
}
