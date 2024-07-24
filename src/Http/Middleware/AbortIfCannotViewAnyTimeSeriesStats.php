<?php

namespace Javaabu\Stats\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Javaabu\Stats\TimeSeriesStats;

class AbortIfCannotViewAnyTimeSeriesStats
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
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

        if (! TimeSeriesStats::canViewAny($user)) {
            abort(403, 'Cannot view any time series stats.');
        }

        return $next($request);
    }
}
