<?php

namespace Javaabu\Stats\Concerns;

use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait HasCategoricalStatsItems
{
    protected ?string $categorical_stats_sort_field = null;

    protected string $categorical_stats_sort_direction = 'asc';

    /** @var (Closure(array{id: int|string, label: string}, array{id: int|string, label: string}): int)|null */
    protected ?Closure $categorical_stats_sort_callback = null;

    /**
     * Get the raw items supplied by the provider.
     *
     * @return iterable<array-key, mixed>
     */
    abstract protected function categoricalStatsItems(): iterable;

    public function getCategoricalStatsCategoryTitle(): string
    {
        return __('Category');
    }

    public function getCategoricalStatsCategoryIdTitle(): string
    {
        return __('Category ID');
    }

    /**
     * Sort category items by a field.
     */
    public function sortCategoricalStatsItemsBy(string $field, string $direction = 'asc'): static
    {
        $this->categorical_stats_sort_field = $field;
        $this->categorical_stats_sort_direction = $this->validateCategoricalStatsSortDirection($direction);
        $this->categorical_stats_sort_callback = null;

        return $this;
    }

    /**
     * Sort category items using a comparison callback.
     *
     * @param  Closure(array{id: int|string, label: string}, array{id: int|string, label: string}): int  $callback
     */
    public function sortCategoricalStatsItemsUsing(Closure $callback): static
    {
        $this->categorical_stats_sort_callback = $callback;
        $this->categorical_stats_sort_field = null;

        return $this;
    }

    /**
     * Get category items, optionally limited to the given ids.
     *
     * @param  list<int|string>|null  $ids
     * @return Collection<int, array{id: int|string, label: string}>
     */
    public function getCategoricalStatsItems(?array $ids = null): Collection
    {
        $items = collect($this->categoricalStatsItems())
            ->map(fn ($label, $id) => $this->normalizeCategoricalStatsItem($label, $id));

        $items = $this->sortCategoricalStatsItems($items);

        if (! is_null($ids)) {
            $string_ids = array_map('strval', $ids);
            $items = $items->filter(fn (array $item) => in_array((string) $item['id'], $string_ids, true));
        }

        return $items->values();
    }

    /**
     * Search category items.
     *
     * @return LengthAwarePaginator<int, array{id: int|string, label: string}>
     */
    public function searchCategoricalStatsItems(?string $search = null, int $per_page = 15): LengthAwarePaginator
    {
        $items = $this->getCategoricalStatsItems();

        if (filled($search)) {
            $items = $items->filter(fn (array $item) => str_contains(mb_strtolower($item['label']), mb_strtolower($search)));
        }

        $page = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $per_page)->values(),
            $items->count(),
            $per_page,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Normalize one category item.
     *
     * @return array{id: int|string, label: string}
     */
    protected function normalizeCategoricalStatsItem(mixed $item, int|string $key): array
    {
        if (is_array($item)) {
            return [
                'id' => $item['id'] ?? $key,
                'label' => (string) ($item['label'] ?? $item['name'] ?? $item['id'] ?? $key),
            ];
        }

        if (is_object($item)) {
            return [
                'id' => $item->id ?? $item->value ?? $key,
                'label' => (string) ($item->label ?? $item->name ?? $item->value ?? $key),
            ];
        }

        return [
            'id' => $key,
            'label' => (string) $item,
        ];
    }

    /**
     * Apply the configured category sorting.
     *
     * @param  Collection<int, array{id: int|string, label: string}>  $items
     * @return Collection<int, array{id: int|string, label: string}>
     */
    protected function sortCategoricalStatsItems(Collection $items): Collection
    {
        if ($this->categorical_stats_sort_callback) {
            return $items->sort($this->categorical_stats_sort_callback)->values();
        }

        if ($this->categorical_stats_sort_field) {
            return $items->sortBy(
                $this->categorical_stats_sort_field,
                SORT_REGULAR,
                $this->categorical_stats_sort_direction === 'desc'
            )->values();
        }

        return $items;
    }

    /**
     * Validate a category sort direction.
     */
    protected function validateCategoricalStatsSortDirection(string $direction): string
    {
        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException("Invalid categorical stats sort direction [{$direction}].");
        }

        return $direction;
    }
}
