<?php

namespace Javaabu\Stats\Repositories\Categorical;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Javaabu\GeneratorHelpers\StringCaser;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Concerns\HasDateRange;
use Javaabu\Stats\Concerns\HasFilters;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Contracts\CategoryProvider;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Support\CategoryProviderFactory;

abstract class AbstractCategoricalStatsRepository implements CategoricalStatsRepository
{
    use HasDateRange;
    use HasFilters;

    protected ?CategoryProvider $resolved_category_provider = null;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(DateRange $date_range = PresetDateRanges::THIS_YEAR, array $filters = [])
    {
        $this->setDateRange($date_range);
        $this->setFilters($filters);
    }

    /**
     * Get the aggregate SQL expression.
     */
    abstract public function getAggregateSql(): string;

    public function getCategoryProvider(): CategoryProvider
    {
        return $this->resolved_category_provider ??= CategoryProviderFactory::make($this->categoryProvider());
    }

    public function canView(?Authorizable $user = null): bool
    {
        return $user && $user->can('view_stats');
    }

    public function metric(): string
    {
        return CategoricalStats::getMetricForStat(get_class($this));
    }

    protected function generateName(): string
    {
        $class_name = StringCaser::title(class_basename($this));

        if (Str::endsWith($class_name, 'Repository')) {
            $class_name = trim(Str::beforeLast($class_name, 'Repository'));
        }

        return $class_name;
    }

    public function getName(): string
    {
        return __($this->generateName());
    }

    public function getAggregateFieldLabel(): string
    {
        return __(StringCaser::title($this->getAggregateFieldName()));
    }

    public function getCategoryFieldAlias(): string
    {
        return 'category';
    }

    public function getCategoryNameFieldAlias(): string
    {
        return $this->getCategoryFieldAlias().'_name';
    }

    public function getCategoryTitle(): string
    {
        return $this->getCategoryProvider()->getCategoricalStatsCategoryTitle();
    }

    public function getCategoryIdTitle(): string
    {
        return $this->getCategoryProvider()->getCategoricalStatsCategoryIdTitle();
    }

    /**
     * Get the query grouped by category.
     *
     * @return Builder<Model>
     */
    public function groupByCategory(): Builder
    {
        $query = $this->filteredQuery();
        $category_field_alias = $query->getQuery()->getGrammar()->wrap($this->getCategoryFieldAlias());

        return $query
            ->select(DB::raw(
                $this->getCategoryField().' as '.$category_field_alias.', '.$this->getAggregateSql()
            ))
            ->groupBy($this->getCategoryField())
            ->orderBy($this->getCategoryField());
    }

    /** @return Collection<int, Model> */
    public function results(): Collection
    {
        $results = $this->groupByCategory()->get();

        $results->each(function ($result) {
            $value = $result->getAttribute($this->getAggregateFieldName());

            if (is_numeric($value)) {
                $result->setAttribute($this->getAggregateFieldName(), $value + 0);
            }
        });

        return $results;
    }

    /**
     * @param  Collection<int, Model>  $results
     * @return array<int|string, int|float>
     */
    public function resultsToArray(Collection $results): array
    {
        return $results->pluck($this->getAggregateFieldName(), $this->getCategoryFieldAlias())->all();
    }

    /**
     * @param  list<int|string>  $categorical_values
     * @return array<int|string, string>
     */
    public function resolveCategoryNames(CategoricalModes $mode, array $categorical_values = []): array
    {
        $ids = $mode == CategoricalModes::ALL ? null : $categorical_values;

        return $this->getCategoryProvider()->getCategoricalStatsItems($ids)->pluck('label', 'id')->all();
    }

    /**
     * @param  list<int|string>  $categorical_values
     * @return array<array-key, mixed>
     */
    public function format(
        string $format,
        ?CategoricalStatsRepository $compare = null,
        CategoricalModes $mode = CategoricalModes::NON_EMPTY,
        array $categorical_values = []
    ): array {
        return CategoricalStats::createFromFormat($format)->format($this, $compare, $mode, $categorical_values);
    }
}
