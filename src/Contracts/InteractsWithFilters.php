<?php
/**
 * Stats Repository base class
 */

namespace Javaabu\Stats\Contracts;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

interface InteractsWithFilters
{
    /**
     * Get the filtered query
     */
    public function filteredQuery(): Builder;

    /**
     * Get all the allowed filters
     */
    public function allowedFilters(): array;

    /**
     * Get all the filters
     */
    public function getFilters(): array;

    /**
     * Get a specific filter
     */
    public function getFilter(string $filter, $default = null);

    /**
     * Check if is an allowed filter
     */
    public function isAllowedFilter(string $filter): bool;

    /**
     * Apply the filters
     */
    public function applyFilters(Builder $query): Builder;

    /**
     * Check if all the filters are allowed
     * @throws InvalidArgumentException
     */
    public function ensureAllFiltersAllowed(array $filters);
}
