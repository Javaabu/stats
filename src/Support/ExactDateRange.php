<?php

namespace Javaabu\Stats\Support;

use Carbon\Carbon;
use Javaabu\Stats\Contracts\DateRange;

class ExactDateRange implements DateRange
{
    public function __construct(protected Carbon $start_date, protected Carbon $end_date) {

    }

    public function getDateFrom(): Carbon
    {
        return $this->start_date;
    }

    public function getDateTo(): Carbon
    {
        return $this->end_date;
    }

    public function getName(): string
    {
        return 'custom';
    }
}
