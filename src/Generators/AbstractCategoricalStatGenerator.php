<?php

namespace Javaabu\Stats\Generators;

use Illuminate\Database\Eloquent\Relations\Relation;

abstract class AbstractCategoricalStatGenerator extends AbstractStatGenerator
{
    protected string $category_model_class;

    protected string $category_field;

    protected string $category_field_alias;

    public function __construct(
        string $name,
        string $model_class,
        string $category_model_class,
        string $category_field
    ) {
        parent::__construct($name, $model_class);

        $this->category_model_class = $this->resolveFullModelClass($category_model_class);
        $this->category_field = $this->qualifyField($category_field);
        $this->category_field_alias = $this->resolveCategoryFieldAlias($this->category_model_class);
    }

    public function render(): string
    {
        return $this->getRenderer()->appendMultipleContent([
            [
                'search' => '{{StatName}}',
                'keep_search' => false,
                'content' => $this->getName(),
            ],
            [
                'search' => '{{ModelClass}}',
                'keep_search' => false,
                'content' => $this->getModelClass(),
            ],
            [
                'search' => '{{Model}}',
                'keep_search' => false,
                'content' => $this->getModelName(),
            ],
            [
                'search' => '{{CategoryModelClass}}',
                'keep_search' => false,
                'content' => $this->getCategoryModelClass(),
            ],
            [
                'search' => '{{CategoryModel}}',
                'keep_search' => false,
                'content' => $this->getCategoryModelName(),
            ],
            [
                'search' => '{{categoryField}}',
                'keep_search' => false,
                'content' => $this->getCategoryField(),
            ],
            [
                'search' => '{{categoryFieldAlias}}',
                'keep_search' => false,
                'content' => $this->getCategoryFieldAlias(),
            ],
            [
                'search' => '{{dateField}}',
                'keep_search' => false,
                'content' => $this->qualifyField('created_at'),
            ],
            [
                'search' => '{{table}}',
                'keep_search' => false,
                'content' => $this->getTable(),
            ],
        ], $this->getRenderer()->loadStub($this->getStub()));
    }

    public function getCategoryModelClass(): string
    {
        return $this->category_model_class;
    }

    public function getCategoryModelName(): string
    {
        return class_basename($this->getCategoryModelClass());
    }

    public function getCategoryField(): string
    {
        return $this->category_field;
    }

    public function getCategoryFieldAlias(): string
    {
        return $this->category_field_alias;
    }

    public function getFullClassName(): string
    {
        return '\\App\\Stats\\Categorical\\'.$this->getName();
    }

    protected function qualifyField(string $field): string
    {
        return str_contains($field, '.') ? $field : $this->getTable().'.'.$field;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $category_model_class
     */
    protected function resolveCategoryFieldAlias(string $category_model_class): string
    {
        $normalized_class = ltrim($category_model_class, '\\');

        foreach (Relation::morphMap() as $alias => $model_class) {
            if (ltrim($model_class, '\\') === $normalized_class) {
                return (string) $alias;
            }
        }

        return 'category';
    }
}
