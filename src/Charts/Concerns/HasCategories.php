<?php

namespace Javaabu\Stats\Charts\Concerns;

use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Exceptions\StatsException;

/**
 * Which categories a categorical chart shows, and how many
 */
trait HasCategories
{
    protected ?CategoricalModes $category_mode = null;

    /** @var list<int|string> */
    protected array $categories = [];

    protected ?int $limit = null;

    /**
     * Which categories to show: with data (non_empty), all, or specific ones
     */
    public function categoryMode(CategoricalModes|string $mode): static
    {
        $this->category_mode = is_string($mode) ? CategoricalModes::from($mode) : $mode;

        return $this;
    }

    public function getCategoryMode(): CategoricalModes
    {
        if ($this->categories !== []) {
            return CategoricalModes::SPECIFIC;
        }

        return $this->category_mode ?? CategoricalStats::defaultMode();
    }

    /**
     * Show only these categories, by id
     *
     * @param  list<int|string>  $ids
     */
    public function categories(array $ids): static
    {
        $this->categories = array_values($ids);

        return $this;
    }

    /**
     * Show only the categories with the highest values
     */
    public function top(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function makeStat(string $metric, DateRange $range, array $filters = []): CategoricalStatsRepository
    {
        if (! class_exists(CategoricalStats::getClassNameForMetric($metric))) {
            throw StatsException::unknownMetric($metric);
        }

        return CategoricalStats::createFromMetric($metric, $range, array_merge($this->filters, $filters));
    }

    /**
     * Labels keyed by category id, in the provider's order, for the values given
     *
     * @param  list<array<int|string, int|float>>  $value_sets
     * @return array<int|string, string>
     */
    protected function categoryNames(CategoricalStatsRepository $stat, array $value_sets): array
    {
        $mode = $this->getCategoryMode();

        if ($mode === CategoricalModes::SPECIFIC) {
            return $stat->resolveCategoryNames($mode, $this->categories);
        }

        if ($mode === CategoricalModes::ALL) {
            return $stat->resolveCategoryNames($mode);
        }

        $ids = [];

        foreach ($value_sets as $values) {
            foreach ($values as $id => $value) {
                $ids[(string) $id] = $id;
            }
        }

        return $ids === [] ? [] : $stat->resolveCategoryNames(CategoricalModes::SPECIFIC, array_values($ids));
    }

    /**
     * @return array<string, mixed>
     */
    protected function categoriesCacheKey(): array
    {
        return [
            'category_mode' => $this->getCategoryMode()->value,
            'categories' => $this->categories,
            'limit' => $this->limit,
        ];
    }
}
