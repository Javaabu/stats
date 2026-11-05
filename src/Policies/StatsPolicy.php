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
 * | export   | the stat's canView()    |
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

    /**
     * Whoever can see a stat can download it. The stat's own canView() decides,
     * so stats that override it keep working; restrict exports in your policy.
     */
    public function export(Authorizable $user, TimeSeriesStatsRepository|CategoricalStatsRepository $stat): bool
    {
        return $stat->canView($user);
    }
}
