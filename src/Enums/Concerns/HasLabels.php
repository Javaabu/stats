<?php

namespace Javaabu\Stats\Enums\Concerns;

/**
 * Translated labels for a backed enum, for selects and headings
 */
trait HasLabels
{
    abstract public function getLabel(): string;

    /**
     * The labels keyed by value
     *
     * @return array<string, string>
     */
    public static function getLabels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->getLabel();
        }

        return $labels;
    }

    /**
     * The cases as a list of options, in order
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->getLabel(),
        ], self::cases());
    }
}
