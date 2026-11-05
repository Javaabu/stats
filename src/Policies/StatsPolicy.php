<?php

namespace Javaabu\Stats\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;

/**
 * Who may see and export a stat
 *
 * | Ability  | Permission              |
 * | -------- | ----------------------- |
 * | view     | view_stats              |
 * | export   | what view needs         |
 *
 * A stat can still decide for itself by overriding `canView()`. Replace the
 * policy with `config('stats.policy')`, `config('permissions.policies')` or
 * `Gate::policy(TimeSeriesStatsRepository::class, ...)` in the app.
 */
class StatsPolicy
{
    public const string VIEW = 'view_stats';

    public function view(Authorizable $user, TimeSeriesStatsRepository|CategoricalStatsRepository $stat): bool
    {
        return $user->can(self::VIEW);
    }

    public function export(Authorizable $user, TimeSeriesStatsRepository|CategoricalStatsRepository $stat): bool
    {
        return $this->view($user, $stat);
    }
}
