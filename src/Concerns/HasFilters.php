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
    public function getFilter(string $filter, $default = null)
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
     * Set the filters
     */
    public function setFilters(array $filters)
    {
        $this->ensureAllFiltersAllowed($filters, true);

        $this->filters = $filters;
    }

    /**
     * Check if all the filters are allowed
     */
    public function ensureAllFiltersAllowed(array $filters, bool $throw_on_fail = false): bool
    {
        $filter_names = collect(array_is_list($filters) ? $filters : array_keys($filters));

        $allowed_filter_names = collect($this->allowedFilters())->map(function (Filter $filter) {
            return $filter->getName();
        });

        if ($throw_on_fail) {
            $diff = $filter_names->diff($allowed_filter_names);

            if ($diff->count()) {
                throw InvalidFiltersException::filtersNotAllowed($diff, $allowed_filter_names);
            }
         } else {
            foreach ($filter_names as $filter) {
                if (! $allowed_filter_names->contains($filter)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get all the allowed filters
     */
    public function allowedFilters(): array
    {
        return [];
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

    /**
     * Check if is an allowed filter
     */
    public function isAllowedFilter(string $filter): bool
    {
        $allowed_filters = $this->allowedFilters();

        /** @var Filter $allowed_filter */
        foreach ($allowed_filters as $allowed_filter) {
            if ($filter == $allowed_filter->getName()) {
                return true;
            }
        }

        return false;
    }
}
