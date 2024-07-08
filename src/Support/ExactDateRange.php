<?php

namespace Javaabu\Stats\Support;

use Carbon\Carbon;
use Javaabu\Stats\Contracts\DateRange;

class ExactDateRange implements DateRange
{
    public function __construct(protected Carbon $start_date, protected Carbon $end_date) {

    }

    public function getDateFrom(?Carbon $min_date = null): Carbon
    {
        return $this->start_date;
    }

    public function getDateTo(?Carbon $max_date = null): Carbon
    {
        return $this->end_date;
    }
}
