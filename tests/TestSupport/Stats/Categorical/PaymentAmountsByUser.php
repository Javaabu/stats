<?php

namespace Javaabu\Stats\Tests\TestSupport\Stats\Categorical;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\Categorical\SumCategoricalStatsRepository;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class PaymentAmountsByUser extends SumCategoricalStatsRepository
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

    public function getFieldToSum(): string
    {
        return 'payments.amount';
    }

    public function getAggregateFieldName(): string
    {
        return 'amount';
    }

    public function canView(?Authorizable $user = null): bool
    {
        return true;
    }
}
