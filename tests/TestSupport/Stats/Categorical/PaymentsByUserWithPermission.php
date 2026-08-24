<?php

namespace Javaabu\Stats\Tests\TestSupport\Stats\Categorical;

use Illuminate\Contracts\Auth\Access\Authorizable;

class PaymentsByUserWithPermission extends PaymentsByUser
{
    public function canView(?Authorizable $user = null): bool
    {
        return $user && $user->can('view_stats');
    }
}
