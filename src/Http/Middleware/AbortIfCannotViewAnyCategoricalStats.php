<?php

namespace Javaabu\Stats\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Javaabu\Stats\CategoricalStats;

class AbortIfCannotViewAnyCategoricalStats
{
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;
        $user = null;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                /** @var Authorizable $user */
                if ($user = Auth::guard($guard)->user()) {
                    break;
                }
            }
        }

        if (! CategoricalStats::canViewAny($user)) {
            abort(403, 'Cannot view any categorical stats.');
        }

        return $next($request);
    }
}
