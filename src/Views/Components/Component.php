<?php

namespace Javaabu\Stats\Views\Components;

use Illuminate\View\Component as BaseComponent;

abstract class Component extends BaseComponent
{
    /**
     * Framework used for this component
     *
     * @var string
     */
    public string $framework;

    /**
     * The view to be used
     *
     * @var string
     */
    protected string $view;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $framework = '')
    {
        if (! $framework) {
            $framework = config('stats.framework');
        }

        $this->framework = $framework;
    }

    public function getView(): string
    {
        return 'stats::{framework}.' . $this->view;
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $view = $this->getView();

        $framework = $this->framework;

        return str_replace('{framework}', $framework, $view);
    }
}
