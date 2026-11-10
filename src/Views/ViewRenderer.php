<?php

namespace Javaabu\Stats\Views;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;
use Javaabu\Stats\Exceptions\StatsException;

/**
 * Renders a package page with the stack chosen in `config('stats.views')`
 */
class ViewRenderer
{
    public const string BLADE = 'blade';

    public const string INERTIA = 'inertia';

    /**
     * @param  string  $page  e.g. `time-series.index`, rendered as the Blade view
     *                        `stats::time-series.index` or the Inertia page `stats::TimeSeries/Index`
     * @param  array<string, mixed>  $data
     */
    public function render(string $page, array $data = []): View|Responsable
    {
        if ($this->isInertia()) {
            return $this->renderInertia($page, $data);
        }

        return view("stats::{$page}", $data);
    }

    public function stack(): string
    {
        return config('stats.views', self::BLADE);
    }

    public function isInertia(): bool
    {
        return $this->stack() === self::INERTIA;
    }

    /**
     * The Inertia page name for a page: `time-series.index` → `stats::TimeSeries/Index`
     */
    public function inertiaPage(string $page): string
    {
        $segments = array_map(fn (string $segment) => str($segment)->studly()->toString(), explode('.', $page));

        return 'stats::' . implode('/', $segments);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderInertia(string $page, array $data): Responsable
    {
        if (! class_exists(Inertia::class)) {
            throw StatsException::inertiaMissing();
        }

        return Inertia::render($this->inertiaPage($page), $data);
    }
}
