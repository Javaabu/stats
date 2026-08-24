<?php

namespace Javaabu\Stats\Support;

use Illuminate\Database\Eloquent\Model;
use Javaabu\GeneratorHelpers\StringCaser;
use UnitEnum;

class CategoryProviderTitleResolver
{
    public static function forModel(Model $model): string
    {
        $model_class = get_class($model);
        $morph_class = $model->getMorphClass();
        $name = ltrim((string) $morph_class, '\\') === ltrim($model_class, '\\')
            ? class_basename($model)
            : (string) $morph_class;

        return __(StringCaser::title($name));
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    public static function forEnum(string $enum): string
    {
        return __(StringCaser::title(class_basename($enum)));
    }

    public static function idTitle(string $category_title): string
    {
        return __(':category ID', ['category' => $category_title]);
    }
}
