<?php

namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Filters\SelectFilter;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;
use Javaabu\Stats\Tests\TestSupport\Enums\PaymentStatus;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;

/**
 * Payments, filterable by status through javaabu/filters
 */
class PaymentsByStatusCount extends CountStatsRepository
{
    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('user', 'user_id'),
            StatsFilter::from(SelectFilter::make('status')->label('ހާލަތު')->options(PaymentStatus::class)),
        ];
    }

    public function query(): Builder
    {
        return Payment::query();
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getDateFieldName(): string
    {
        return 'paid_at';
    }

    public function getAggregateFieldName(): string
    {
        return 'count';
    }

    public function getName(): string
    {
        return 'ފައިސާ ދެއްކުން';
    }
}
