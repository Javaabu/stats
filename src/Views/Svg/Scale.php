<?php

namespace Javaabu\Stats\Views\Svg;

/**
 * A linear value scale with "nice" ticks (0, 5, 10, 15 …), like d3's
 */
class Scale
{
    /** @var list<float> */
    public array $ticks = [];

    public float $min;

    public float $max;

    /**
     * @param  list<int|float>  $values
     */
    public function __construct(array $values, int $tick_count = 5)
    {
        $min = $values === [] ? 0 : min(0, min($values));
        $max = $values === [] ? 0 : max(0, max($values));

        if ($min == $max) {
            $max = $min + 1;
        }

        $step = static::niceStep(($max - $min) / max(1, $tick_count));

        $this->min = floor($min / $step) * $step;
        $this->max = ceil($max / $step) * $step;

        for ($tick = $this->min; $tick <= $this->max + $step / 2; $tick += $step) {
            $this->ticks[] = round($tick, 10);
        }
    }

    public static function niceStep(float $raw): float
    {
        $exponent = floor(log10($raw));
        $fraction = $raw / (10 ** $exponent);

        $nice = match (true) {
            $fraction <= 1 => 1,
            $fraction <= 2 => 2,
            $fraction <= 2.5 => 2.5,
            $fraction <= 5 => 5,
            default => 10,
        };

        return $nice * (10 ** $exponent);
    }

    /**
     * Where a value falls between the given pixel positions (bottom to top)
     */
    public function position(float $value, float $from, float $to): float
    {
        return $from + ($value - $this->min) / ($this->max - $this->min) * ($to - $from);
    }
}
