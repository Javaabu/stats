<?php

namespace App\Stats\TimeSeries;

use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class PaymentsCount extends CountStatsRepository
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
        return 'count';
    }
}
