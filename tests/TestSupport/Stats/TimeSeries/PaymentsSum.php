<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\SumStatsRepository;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;

class PaymentsSum extends SumStatsRepository
{
    /**
     * Get all the allowed filters
     */
    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('user', 'user_id'),
        ];
    }

    /**
     * Get the base query
     */
    public function query(): Builder
    {
        return Payment::query();
    }

    /**
     * Check whether the given user can view the stat
     */
    public function canView(?Authorizable $user = null): bool
    {
        return true;
    }

    public function getFieldToSum(): string
    {
        return 'amount';
    }

    public function getTable(): string
    {
        return 'payments';
    }

    /**
     * Get the date field name
     */
    public function getDateFieldName(): string
    {
        return 'paid_at';
    }

    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string
    {
        return 'total';
    }
}
