<?php

namespace Javaabu\Stats\Tests\Unit\Support;

use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\TestCase;

class ExactDateRangeTest extends TestCase
{
    /** @test */
    public function it_can_generate_correct_previous_date_range(): void
    {
        $date_range = new ExactDateRange('2024-07-08 10:07 PM', '2024-07-08 11:07 PM');
        $previous_date_range = $date_range->getPreviousDateRange();

        $this->assertEquals('2024-07-08 21:06:59', $previous_date_range->getDateFrom(), 'Incorrect start of previous date');
        $this->assertEquals('2024-07-08 22:06:59', $previous_date_range->getDateTo(), 'Incorrect end of previous date');
    }
}
