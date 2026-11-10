<?php

namespace Javaabu\Stats\Forms\Fields;

use Closure;
use Javaabu\Stencil\Fields\Select;

/**
 * A searchable select of a categorical stat's categories, loaded over AJAX
 * from the categories endpoint for the metric chosen in another field
 * (javaabu/forms' filtered selects: `filter[metric]=…`).
 */
class CategorySelect extends Select
{
    protected string|Closure|null $parent_selector = null;

    protected string|Closure|null $filter_field = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()->multiple();
    }

    /**
     * Load the options for the value of another field
     */
    public function dependsOn(string|Closure $parent_selector, string|Closure $filter_field): static
    {
        $this->parent_selector = $parent_selector;
        $this->filter_field = $filter_field;

        return $this;
    }

    protected function getFieldProps(): array
    {
        return array_filter([
            ...parent::getFieldProps(),
            'parent' => $this->evaluate($this->parent_selector),
            'filterField' => $this->evaluate($this->filter_field),
        ], fn ($value) => $value !== null);
    }
}
