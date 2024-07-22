<?php
namespace Javaabu\Stats\Tests\TestSupport\Stats\TimeSeries;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;

class PaymentsCount extends CountStatsRepository
{
    /**
     * @var string
     */
    protected string $table = 'payments';

    protected string $date_field = 'paid_at';

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
}
