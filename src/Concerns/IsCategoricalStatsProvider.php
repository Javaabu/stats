<?php

namespace Javaabu\Stats\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Javaabu\Stats\Support\CategoryProviderTitleResolver;

/**
 * @template TModel of Model
 *
 * @mixin TModel
 */
trait IsCategoricalStatsProvider
{
    /** @use SortsCategoricalStatsQueries<TModel> */
    use SortsCategoricalStatsQueries;

    public function getCategoricalStatsCategoryTitle(): string
    {
        return CategoryProviderTitleResolver::forModel($this);
    }

    public function getCategoricalStatsCategoryIdTitle(): string
    {
        return CategoryProviderTitleResolver::idTitle($this->getCategoricalStatsCategoryTitle());
    }

    public function getCategoricalStatsIdField(): string
    {
        return $this->getKeyName();
    }

    public function getCategoricalStatsLabelField(): string
    {
        if ($this->hasGetMutator('admin_link_name') || $this->hasAttributeGetMutator('admin_link_name')) {
            return 'admin_link_name';
        }

        return 'name';
    }

    public function getCategoricalStatsSearchField(): string
    {
        return 'name';
    }

    /**
     * @param  list<int|string>|null  $ids
     * @return Collection<int, array{id: int|string, label: string}>
     */
    public function getCategoricalStatsItems(?array $ids = null): Collection
    {
        $query = $this->newQuery();

        if (! is_null($ids)) {
            $query->whereIn($this->getCategoricalStatsIdField(), $ids);
        }

        $this->applyCategoricalStatsSort($query, $this->getCategoricalStatsIdField());

        return $this->mapCategoricalStatsItems($query);
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int|string, label: string}>
     */
    public function searchCategoricalStatsItems(?string $search = null, int $per_page = 15): LengthAwarePaginator
    {
        $query = $this->newQuery();

        if (filled($search)) {
            if ($this->hasNamedScope('categoricalStatsSearch')) {
                $query->categoricalStatsSearch($search);
            } elseif ($this->hasNamedScope('search')) {
                $query->search($search);
            } else {
                $query->where($this->getCategoricalStatsSearchField(), 'like', '%'.$search.'%');
            }
        }

        $this->applyCategoricalStatsSort($query, $this->getCategoricalStatsIdField());

        $paginator = $query->paginate($per_page);
        $paginator->setCollection($this->mapCategoricalStatsModels($paginator->getCollection()));

        return $paginator;
    }

    /**
     * @param  Builder<TModel>  $query
     * @return Collection<int, array{id: int|string, label: string}>
     */
    protected function mapCategoricalStatsItems(Builder $query): Collection
    {
        return $this->mapCategoricalStatsModels($query->get());
    }

    /**
     * @param  Collection<int, TModel>  $items
     * @return Collection<int, array{id: int|string, label: string}>
     */
    protected function mapCategoricalStatsModels(Collection $items): Collection
    {
        return $items
            ->map(fn (Model $model) => [
                'id' => $model->getAttribute($this->getCategoricalStatsIdField()),
                'label' => (string) $model->getAttribute($this->getCategoricalStatsLabelField()),
            ])
            ->values();
    }
}
