<?php

namespace Javaabu\Stats\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Javaabu\Stats\CategoryProviders\ArrayCategoryProvider;
use Javaabu\Stats\CategoryProviders\EloquentCategoryProvider;
use Javaabu\Stats\CategoryProviders\EnumCategoryProvider;
use Javaabu\Stats\Contracts\CategoryProvider;
use UnitEnum;

class CategoryProviderFactory
{
    /**
     * Normalize a category provider source.
     *
     * @param  CategoryProvider|Builder<Model>|Model|Collection<array-key, mixed>|array<array-key, mixed>|class-string<CategoryProvider|Model|UnitEnum>  $provider
     */
    public static function make(mixed $provider): CategoryProvider
    {
        if ($provider instanceof CategoryProvider) {
            return $provider;
        }

        if (is_string($provider) && is_subclass_of($provider, CategoryProvider::class)) {
            return new $provider;
        }

        if ($provider instanceof Builder || $provider instanceof Model) {
            return new EloquentCategoryProvider($provider);
        }

        if ($provider instanceof Collection || is_array($provider)) {
            return new ArrayCategoryProvider($provider);
        }

        if (is_string($provider) && enum_exists($provider) && is_subclass_of($provider, UnitEnum::class)) {
            return new EnumCategoryProvider($provider);
        }

        if (is_string($provider) && is_subclass_of($provider, Model::class)) {
            return new EloquentCategoryProvider($provider);
        }

        throw new InvalidArgumentException('Unsupported categorical provider ['.get_debug_type($provider).'].');
    }
}
