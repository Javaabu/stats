<?php

namespace App\Stats\TimeSeries;

use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\SumStatsRepository;

class PaymentsSum extends SumStatsRepository
{
    public function query(): Builder
    {
        return Payment::query();
    }

    public function allowedFilters(): array
    {
        return [
            // define your filters here
        ];
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getAggregateFieldName(): string
    {
        return 'total';
    }

    public function getFieldToSum(): string
    {
        // TODO: return the field to sum
    }
}
