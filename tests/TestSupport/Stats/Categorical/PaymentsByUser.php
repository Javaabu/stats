<?php

namespace Javaabu\Stats\Tests\TestSupport\Stats\Categorical;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\Categorical\CountCategoricalStatsRepository;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class PaymentsByUser extends CountCategoricalStatsRepository
{
    public function query(): Builder
    {
        return Payment::query();
    }

    public function categoryProvider(): mixed
    {
        return User::class;
    }

    public function getCategoryField(): string
    {
        return 'payments.user_id';
    }

    public function getDateField(): string
    {
        return 'payments.paid_at';
    }

    public function getAggregateFieldName(): string
    {
        return 'count';
    }

    public function allowedFilters(): array
    {
        return [
            StatsFilter::exact('user', 'user_id'),
        ];
    }

    public function canView(?Authorizable $user = null): bool
    {
        return true;
    }
}
