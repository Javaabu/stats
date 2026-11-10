<?php

namespace Javaabu\Stats\Filters;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Filters\Filter as FiltersFilter;
use Javaabu\Stats\Contracts\Filter;
use Javaabu\Stats\Contracts\HasFields;
use Javaabu\Stats\Contracts\InteractsWithFilters;
use Javaabu\Stats\Exceptions\StatsException;

/**
 * A javaabu/filters filter used as a stats filter: its fields go in the stats
 * form, its value is read from its own query parameters, and it filters the
 * stat's query the way it filters an index page.
 *
 * ```php
 * public function allowedFilters(): array
 * {
 *     return [
 *         StatsFilter::from(SelectFilter::make('status')->options(PaymentStatus::class)),
 *         StatsFilter::from(SelectFilter::make('island_id')->relationship('island', 'name')),
 *     ];
 * }
 * ```
 */
class ModelFilter implements Filter, HasFields
{
    /** Parameters the stats forms use, which a filter can't be named after */
    public const array RESERVED = [
        'metric', 'with', 'mode', 'variant', 'format', 'filters', 'categorical_values',
        'date_range', 'date_from', 'date_to',
        'compare', 'compare_to', 'compare_date_range', 'compare_date_from', 'compare_date_to',
        'page', 'per_page', 'search',
    ];

    public function __construct(protected FiltersFilter $filter)
    {
        foreach ([$filter->getName(), ...$filter->getQueryKeys()] as $name) {
            if (in_array($name, self::RESERVED, true)) {
                throw StatsException::reservedFilterName($name);
            }
        }
    }

    public function getName(): string
    {
        return $this->filter->getName();
    }

    public function getFilter(): FiltersFilter
    {
        return $this->filter;
    }

    public function apply(Builder $query, $value, InteractsWithFilters $stat): Builder
    {
        $value = $this->filter->normalizeValue($value);

        if ($value !== null) {
            $this->filter->apply($query, $value);
        }

        return $query;
    }

    public function getFields(): array
    {
        return $this->filter->getComponents();
    }

    public function getQueryKeys(): array
    {
        return $this->filter->getQueryKeys();
    }

    public function resolveValue(array $input): mixed
    {
        return $this->filter->resolveValue($input);
    }
}
