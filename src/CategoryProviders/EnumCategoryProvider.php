<?php

namespace Javaabu\Stats\CategoryProviders;

use BackedEnum;
use Closure;
use Illuminate\Support\Str;
use Javaabu\Stats\Concerns\HasCategoricalStatsItems;
use Javaabu\Stats\Contracts\CategoryProvider;
use Javaabu\Stats\Support\CategoryProviderTitleResolver;
use UnitEnum;

/**
 * @template TEnum of UnitEnum
 */
class EnumCategoryProvider implements CategoryProvider
{
    use HasCategoricalStatsItems;

    /** @var class-string<TEnum> */
    protected string $enum;

    public function getCategoricalStatsCategoryTitle(): string
    {
        return CategoryProviderTitleResolver::forEnum($this->enum);
    }

    public function getCategoricalStatsCategoryIdTitle(): string
    {
        return CategoryProviderTitleResolver::idTitle($this->getCategoricalStatsCategoryTitle());
    }

    /**
     * @param  class-string<TEnum>  $enum
     * @param  (Closure(array{id: int|string, label: string}, array{id: int|string, label: string}): int)|null  $sort_callback
     */
    public function __construct(
        string $enum,
        string $sort_field = '',
        string $sort_direction = 'asc',
        ?Closure $sort_callback = null
    ) {
        $this->enum = $enum;

        if ($sort_callback) {
            $this->sortCategoricalStatsItemsUsing($sort_callback);
        } elseif ($sort_field) {
            $this->sortCategoricalStatsItemsBy($sort_field, $sort_direction);
        }
    }

    /** @return iterable<int, array{id: int|string, label: string}> */
    protected function categoricalStatsItems(): iterable
    {
        return collect($this->enum::cases())->map(function (UnitEnum $case) {
            $id = $case instanceof BackedEnum ? $case->value : $case->name;

            if (method_exists($case, 'getLabel')) {
                $label = $case->getLabel();
            } elseif (method_exists($case, 'label')) {
                $label = $case->label();
            } else {
                $label = Str::headline($case->name);
            }

            return compact('id', 'label');
        });
    }
}
