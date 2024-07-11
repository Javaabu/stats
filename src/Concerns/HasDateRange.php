<?php

namespace Javaabu\Stats\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\InteractsWithDateRange;

/** @var $this InteractsWithDateRange */
trait HasDateRange
{
    protected Carbon $date_from;
    protected Carbon $date_to;
    protected DateRange $date_range;

    public function getDateFrom(): Carbon
    {
        return $this->date_from;
    }

    public function getDateTo(): Carbon
    {
        return $this->date_to;
    }

    public function getDateRange(): DateRange
    {
        return $this->date_range;
    }

    public function formattedDateRange(string $format = 'Y-m-d H:i', string $separator = ' - '): string
    {
        return $this->getDateFrom()->format($format) . $separator . $this->getDateTo()->format($format);
    }

    public function setDateFrom(Carbon|string $date_from)
    {
        $this->date_from = Carbon::parse($date_from);
    }

    public function setDateTo(Carbon|string $date_to)
    {
        $this->date_to = Carbon::parse($date_to);
    }

    public function getDateField(): string
    {
        return 'created_at';
    }

    public function getMaxDate(): ?Carbon
    {
        $max = $this->query()->max($this->getDateField());

        return $max ? Carbon::parse($max) : null;
    }

    public function getMinDate(): ?Carbon
    {
        $min = $this->query()->min($this->getDateField());

        return $min ? Carbon::parse($min) : null;
    }

    public function setDateRange(DateRange $date_range)
    {
        $date_from = $date_range->getDateFrom($this->getMinDate());
        $date_to = $date_range->getDateTo($this->getMaxDate());

        $this->date_range = $date_range;

        $this->setDateFrom($date_from);
        $this->setDateTo($date_to);
    }

    /**
     * Apply the date filters
     */
    public function applyDateFilters(Builder $query): Builder
    {
        return $query->whereBetween($this->getDateField(), [$this->getDateFrom(), $this->getDateTo()])
            ->latest($this->getDateField());
    }

    /**
     * Get the filtered query without date filters
     */
    public function filteredQueryWithoutDateFilters(): Builder
    {
        return $this->applyFilters($this->query());
    }
}
