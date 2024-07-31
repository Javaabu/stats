<?php

namespace Javaabu\Stats\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Javaabu\GeneratorHelpers\StringCaser;
use Javaabu\GeneratorHelpers\StubRenderer;

abstract class AbstractStatGenerator
{
    protected string $name;
    protected string $model_class;
    protected string $table;
    protected string $metric;
    protected StubRenderer $renderer;

    /**
     * Constructor
     */
    public function __construct(string $name, string $model_class)
    {
        $this->name = StringCaser::studly($name);
        $this->metric = StringCaser::snake($this->name);
        $this->model_class = $this->resolveFullModelClass($model_class);
        $this->table = $this->resolveTableName($this->model_class);
        $this->renderer = app()->make(StubRenderer::class);
    }

    public function render(): string
    {
        $stub = $this->getStub();

        $renderer = $this->getRenderer();

        $template = $renderer->loadStub($stub);

        return $renderer->appendMultipleContent([
            [
                'search' => '{{StatName}}',
                'keep_search' => false,
                'content' => $this->getName(),
            ],
            [
                'search' => '{{table}}',
                'keep_search' => false,
                'content' => $this->getTable(),
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
        ], $template);
    }

    public abstract function getStub(): string;

    public function getRenderer(): StubRenderer
    {
        return $this->renderer;
    }

    public function resolveFullModelClass(string $model_class): string
    {
        if (! class_exists($model_class)) {
            // check if morph
            $morph_class = Model::getActualClassNameForMorph($model_class);

            if ($morph_class != $model_class) {
                return $morph_class;
            }

            // add \App\Models\ to front
            $model_class = ltrim($model_class, '\\');

            if (! Str::startsWith($model_class, 'App\\Models\\')) {
                $model_class = '\\App\\Models\\' . $model_class;
            }
        }

        return $model_class;
    }

    public function resolveTableName(string $model_class): string
    {
        if (! class_exists($model_class)) {
            return StringCaser::pluralSnake($model_class);
        }

        return (new $model_class)->getTable();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getModelClass(): string
    {
        return $this->model_class;
    }

    public function getModelName(): string
    {
        return class_basename($this->getModelClass());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMetric(): string
    {
        return $this->metric;
    }

    public function getFullClassName(): string
    {
        return '\\App\\Stats\\TimeSeries\\' . $this->getName();
    }
}
