<?php

namespace Javaabu\Stats\Concerns;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\InteractsWithDateRange;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Support\ExactDateRange;

/** @var $this InteractsWithDateRange */
trait HasDateRange
{
    protected CarbonInterface $date_from;

    protected CarbonInterface $date_to;

    protected DateRange $date_range;

    public function getDateFrom(): CarbonInterface
    {
        return $this->date_from;
    }

    public function getDateTo(): CarbonInterface
    {
        return $this->date_to;
    }

    public function getDateRange(): DateRange
    {
        return $this->date_range;
    }

    public function formattedDateRange(string $format = 'YYYY-MM-DD HH:mm', string $separator = ' - '): string
    {
        return $this->getDateFrom()->isoFormat($format) . $separator . $this->getDateTo()->isoFormat($format);
    }

    public function setDateFrom(DateTimeInterface|string $date_from)
    {
        $date_from = Date::parse($date_from);

        $this->setDateRange(new ExactDateRange($date_from, $this->getDateTo()));
    }

    public function setDateTo(DateTimeInterface|string $date_to)
    {
        $date_to = Date::parse($date_to);

        $this->setDateRange(new ExactDateRange($this->getDateFrom(), $date_to));
    }

    public function getDateField(): string
    {
        return 'created_at';
    }

    public function getMaxDate(?CarbonInterface $fallback = null): ?CarbonInterface
    {
        $max = $this->query()->max($this->getDateField());

        return $max ? Date::parse($max) : $fallback;
    }

    public function getMinDate(?CarbonInterface $fallback = null): ?CarbonInterface
    {
        $min = $this->query()->min($this->getDateField());

        return $min ? Date::parse($min) : $fallback;
    }

    public function setDateRange(DateRange $date_range)
    {
        $date_from = $date_range == PresetDateRanges::LIFETIME ? $this->getMinDate($date_range->getDateFrom()) : $date_range->getDateFrom();
        $date_to = $date_range == PresetDateRanges::LIFETIME ? $this->getMaxDate($date_range->getDateTo()) : $date_range->getDateTo();

        $this->date_range = $date_range;

        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    /**
     * Apply the date filters
     */
    public function applyDateFilters(Builder $query): Builder
    {
        return $query->whereBetween($this->getDateField(), [$this->getDateFrom(), $this->getDateTo()]);
    }

    /**
     * Get the filtered query without date filters
     */
    public function filteredQueryWithoutDateFilters(): Builder
    {
        return $this->applyFilters($this->query());
    }

    /**
     * Get the interval length for the given mode
     */
    public function interval(TimeSeriesModes $mode): int
    {
        return $mode->interval($this->getDateFrom(), $this->getDateTo());
    }
}
