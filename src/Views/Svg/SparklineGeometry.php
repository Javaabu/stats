<?php

namespace Javaabu\Stats\Views\Svg;

/**
 * A sparkline's line and area, with no axes. Runs right to left in RTL.
 */
class SparklineGeometry
{
    public const int WIDTH = 120;

    /**
     * @param  list<int|float|null>  $values
     * @return array{width: int, height: int, line: ?string, area: ?string}
     */
    public static function make(array $values, int $height = 32, bool $rtl = false): array
    {
        $numbers = array_values(array_filter($values, fn ($value) => $value !== null));
        $min = $numbers === [] ? 0 : min($numbers);
        $max = $numbers === [] ? 0 : max($numbers);
        $range = $max - $min ?: 1;
        $count = count($values);
        $points = [];

        foreach ($values as $i => $value) {
            if ($value === null) {
                $points[] = null;

                continue;
            }

            $x = $count > 1 ? $i * self::WIDTH / ($count - 1) : self::WIDTH / 2;
            $x = $rtl ? self::WIDTH - $x : $x;
            $y = $max == $min ? $height / 2 : 2 + ($max - $value) / $range * ($height - 4);

            $points[] = ['x' => round($x, 2), 'y' => round($y, 2), 'y0' => $height];
        }

        return [
            'width' => self::WIDTH,
            'height' => $height,
            'line' => CartesianChart::linePath($points),
            'area' => CartesianChart::areaPath($points),
        ];
    }
}
