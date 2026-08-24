<?php

namespace Javaabu\Stats\CategoryProviders;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Javaabu\Stats\Concerns\SortsCategoricalStatsQueries;
use Javaabu\Stats\Contracts\CategoryProvider;
use Javaabu\Stats\Support\CategoryProviderTitleResolver;

/**
 * @template TModel of Model
 */
class EloquentCategoryProvider implements CategoryProvider
{
    /** @use SortsCategoricalStatsQueries<TModel> */
    use SortsCategoricalStatsQueries;

    /** @var Builder<TModel> */
    protected Builder $query;

    protected string $id_field;

    protected string $label_field;

    public function getCategoricalStatsCategoryTitle(): string
    {
        $model = $this->query->getModel();

        if (method_exists($model, 'getCategoricalStatsCategoryTitle')) {
            return $model->getCategoricalStatsCategoryTitle();
        }

        return CategoryProviderTitleResolver::forModel($model);
    }

    public function getCategoricalStatsCategoryIdTitle(): string
    {
        $model = $this->query->getModel();

        if (method_exists($model, 'getCategoricalStatsCategoryIdTitle')) {
            return $model->getCategoricalStatsCategoryIdTitle();
        }

        return CategoryProviderTitleResolver::idTitle($this->getCategoricalStatsCategoryTitle());
    }

    /**
     * @param  Builder<TModel>|TModel|class-string<TModel>  $source
     * @param  (Closure(Builder<TModel>): mixed)|null  $sort_query
     */
    public function __construct(
        Builder|Model|string $source,
        string $id_field = '',
        string $label_field = '',
        string $sort_field = '',
        string $sort_direction = 'asc',
        ?Closure $sort_query = null
    ) {
        if ($source instanceof Builder) {
            $this->query = clone $source;
            $model = $source->getModel();
        } else {
            $model = is_string($source) ? new $source : $source;
            $this->query = $model->newQuery();
        }

        $this->id_field = $id_field ?: $model->getKeyName();

        if ($label_field) {
            $this->label_field = $label_field;
        } elseif ($model->hasGetMutator('admin_link_name') || $model->hasAttributeGetMutator('admin_link_name')) {
            $this->label_field = 'admin_link_name';
        } elseif (method_exists($model, 'getCategoricalStatsLabelField')) {
            $this->label_field = $model->getCategoricalStatsLabelField();
        } else {
            $this->label_field = 'name';
        }

        if ($sort_query) {
            $this->sortCategoricalStatsItemsUsing($sort_query);
        } elseif ($sort_field) {
            $this->sortCategoricalStatsItemsBy($sort_field, $sort_direction);
        }
    }

    /**
     * @param  list<int|string>|null  $ids
     * @return Collection<int, array{id: int|string, label: string}>
     */
    public function getCategoricalStatsItems(?array $ids = null): Collection
    {
        $query = clone $this->query;

        if (! is_null($ids)) {
            $query->whereIn($this->id_field, $ids);
        }

        $this->applyCategoricalStatsSort($query, $this->id_field);

        return $this->getItems($query);
    }

    /**
     * @return LengthAwarePaginator<int, array{id: int|string, label: string}>
     */
    public function searchCategoricalStatsItems(?string $search = null, int $per_page = 15): LengthAwarePaginator
    {
        $query = clone $this->query;

        if (filled($search)) {
            if ($query->getModel()->hasNamedScope('categoricalStatsSearch')) {
                $query->categoricalStatsSearch($search);
            } elseif ($query->getModel()->hasNamedScope('search')) {
                $query->search($search);
            } else {
                $search_field = method_exists($query->getModel(), 'getCategoricalStatsSearchField')
                    ? $query->getModel()->getCategoricalStatsSearchField()
                    : ($this->label_field == 'admin_link_name' ? 'name' : $this->label_field);

                $query->where($search_field, 'like', '%'.$search.'%');
            }
        }

        $this->applyCategoricalStatsSort($query, $this->id_field);

        $paginator = $query->paginate($per_page);
        $paginator->setCollection($this->mapItems($paginator->getCollection()));

        return $paginator;
    }

    /**
     * @param  Builder<TModel>  $query
     * @return Collection<int, array{id: int|string, label: string}>
     */
    protected function getItems(Builder $query): Collection
    {
        return $this->mapItems($query->get());
    }

    /**
     * @param  Collection<int, TModel>  $items
     * @return Collection<int, array{id: int|string, label: string}>
     */
    protected function mapItems(Collection $items): Collection
    {
        return $items
            ->map(fn (Model $model) => [
                'id' => $model->getAttribute($this->id_field),
                'label' => (string) $model->getAttribute($this->label_field),
            ])
            ->values();
    }
}
