<?php

namespace Javaabu\Stats\CategoryProviders;

use Closure;
use Javaabu\Stats\Concerns\HasCategoricalStatsItems;
use Javaabu\Stats\Contracts\CategoryProvider;

class ArrayCategoryProvider implements CategoryProvider
{
    use HasCategoricalStatsItems;

    /** @var iterable<array-key, mixed> */
    protected iterable $categories;

    /**
     * @param  iterable<array-key, mixed>  $categories
     * @param  (Closure(array{id: int|string, label: string}, array{id: int|string, label: string}): int)|null  $sort_callback
     */
    public function __construct(
        iterable $categories,
        string $sort_field = '',
        string $sort_direction = 'asc',
        ?Closure $sort_callback = null
    ) {
        $this->categories = $categories;

        if ($sort_callback) {
            $this->sortCategoricalStatsItemsUsing($sort_callback);
        } elseif ($sort_field) {
            $this->sortCategoricalStatsItemsBy($sort_field, $sort_direction);
        }
    }

    /**
     * @return iterable<array-key, mixed>
     */
    protected function categoricalStatsItems(): iterable
    {
        return $this->categories;
    }
}
