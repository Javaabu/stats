<?php

namespace Javaabu\Stats\Views\Svg;

/**
 * Slices of a pie or donut as SVG paths. Slices start at 12 o'clock and run
 * clockwise, or counter-clockwise right to left, as in Onu's PieChart.
 */
class PieGeometry
{
    public const int SIZE = 220;

    /**
     * @param  list<int|float>  $values
     * @return list<array{path: string, share: float}>
     */
    public static function slices(array $values, bool $donut = false, bool $rtl = false): array
    {
        $total = array_sum(array_map(fn ($value) => max(0, $value), $values));
        $center = self::SIZE / 2;
        $outer = $center - 4;
        $inner = $donut ? $outer * 0.6 : 0;
        $angle = 0.0;
        $slices = [];

        foreach ($values as $value) {
            $share = $total > 0 ? max(0, $value) / $total : 0;
            $sweep = $share * 2 * M_PI;

            $slices[] = [
                'path' => static::arc($center, $outer, $inner, $angle, $angle + $sweep, $rtl),
                'share' => $share,
            ];

            $angle += $sweep;
        }

        return $slices;
    }

    protected static function arc(float $c, float $r, float $r0, float $start, float $end, bool $rtl): string
    {
        if ($end - $start <= 0) {
            return '';
        }

        if ($end - $start >= 2 * M_PI - 1e-6) {
            $end = $start + 2 * M_PI - 1e-4;
        }

        $large = $end - $start > M_PI ? 1 : 0;
        $sweep = $rtl ? 0 : 1;

        [$x1, $y1] = static::point($c, $r, $start, $rtl);
        [$x2, $y2] = static::point($c, $r, $end, $rtl);

        if ($r0 <= 0) {
            return "M{$c} {$c} L{$x1} {$y1} A{$r} {$r} 0 {$large} {$sweep} {$x2} {$y2} Z";
        }

        [$x3, $y3] = static::point($c, $r0, $end, $rtl);
        [$x4, $y4] = static::point($c, $r0, $start, $rtl);
        $back = $rtl ? 1 : 0;

        return "M{$x1} {$y1} A{$r} {$r} 0 {$large} {$sweep} {$x2} {$y2} L{$x3} {$y3} A{$r0} {$r0} 0 {$large} {$back} {$x4} {$y4} Z";
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected static function point(float $c, float $r, float $angle, bool $rtl): array
    {
        $x = $c + $r * sin($angle) * ($rtl ? -1 : 1);
        $y = $c - $r * cos($angle);

        return [round($x, 2), round($y, 2)];
    }
}
