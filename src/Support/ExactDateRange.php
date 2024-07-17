<?php

namespace Javaabu\Stats\Support;

use Carbon\Carbon;
use Carbon\Month;
use Carbon\WeekDay;
use DateTimeInterface;
use Illuminate\Testing\Exceptions\InvalidArgumentException;
use Javaabu\Stats\Contracts\DateRange;

class ExactDateRange implements DateRange
{
    protected Carbon $date_from;
    protected Carbon $date_to;

    public function __construct(
        DateTimeInterface|WeekDay|Month|string|int|float $date_from,
        DateTimeInterface|WeekDay|Month|string|int|float $date_to
    ) {
        $date_from = Carbon::parse($date_from);
        $date_to = Carbon::parse($date_to);

        if ($date_to < $date_from) {
            throw new InvalidArgumentException('date_to cannot be less than date_from');
        }

        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function getDateFrom(): Carbon
    {
        return $this->date_from;
    }

    public function getDateTo(): Carbon
    {
        return $this->date_to;
    }

    public function getName(): string
    {
        return 'custom';
    }

    public function getPreviousDateRange(): DateRange
    {
        $date_from = $this->date_from;
        $date_to = $this->date_to;

        $interval = $date_from->diff($date_to);

        $prev_date_from = $date_from->copy()->sub($interval)->subSecond();
        $prev_date_to = $prev_date_from->copy()->add($interval);

        return new ExactDateRange($prev_date_from, $prev_date_to);
    }
}
