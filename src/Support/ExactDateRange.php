<?php

namespace Javaabu\Stats\Support;

use Carbon\CarbonInterface;
use Carbon\Month;
use Carbon\WeekDay;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Javaabu\Stats\Contracts\DateRange;

class ExactDateRange implements DateRange
{
    protected CarbonInterface $date_from;

    protected CarbonInterface $date_to;

    public function __construct(
        DateTimeInterface|WeekDay|Month|string|int|float $date_from,
        DateTimeInterface|WeekDay|Month|string|int|float $date_to
    ) {
        $date_from = Date::parse($date_from);
        $date_to = Date::parse($date_to);

        if ($date_to < $date_from) {
            throw new InvalidArgumentException('date_to cannot be less than date_from');
        }

        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function getDateFrom(): CarbonInterface
    {
        return $this->date_from;
    }

    public function getDateTo(): CarbonInterface
    {
        return $this->date_to;
    }

    public function getName(): string
    {
        return 'custom';
    }

    /**
     * The period of the same length that ends the second before this one starts
     */
    public function getPreviousDateRange(): DateRange
    {
        $length = (int) floor($this->date_from->diffInSeconds($this->date_to, true));

        $prev_date_to = $this->date_from->copy()->startOfSecond()->subSecond();
        $prev_date_from = $prev_date_to->copy()->subSeconds($length);

        return new ExactDateRange($prev_date_from, $prev_date_to);
    }
}
