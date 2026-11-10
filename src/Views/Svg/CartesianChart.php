<?php

namespace Javaabu\Stats\Views\Svg;

use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Support\Numbers;

/**
 * Lays out a time series or category chart (lines, areas or bars) as SVG
 * shapes for the Blade views. Everything is computed left to right, then
 * mirrored for right-to-left locales, so the newest step and the value
 * axis sit where Onu's React charts put them.
 */
class CartesianChart
{
    public const int WIDTH = 640;

    public const int DEFAULT_HEIGHT = 300;

    protected const int MAX_X_LABELS = 8;

    /**
     * @param  array<string, mixed>  $chart  a TimeSeriesChart or CategoryChart's array
     * @return array<string, mixed>
     */
    public static function make(array $chart, bool $rtl = false): array
    {
        return (new static($chart, $rtl))->build();
    }

    /** @var list<array<string, mixed>> */
    protected array $rows;

    /** @var list<string> */
    protected array $labels;

    /** @var list<string> */
    protected array $keys;

    protected string $variant;

    protected bool $stacked;

    protected bool $horizontal;

    protected ?string $previous_key;

    protected string $format;

    protected int $width = self::WIDTH;

    protected int $height;

    /**
     * @param  array<string, mixed>  $chart
     */
    public function __construct(protected array $chart, protected bool $rtl = false)
    {
        $this->rows = array_values($chart['data'] ?? []);
        $this->keys = array_keys($chart['config'] ?? []);
        $this->variant = $chart['variant'] ?? 'line';
        $this->stacked = (bool) ($chart['stacked'] ?? false);
        $this->horizontal = ($chart['orientation'] ?? null) === 'horizontal' && $this->variant === 'bar';
        $this->previous_key = $chart['comparison']['previousKey'] ?? null;
        $this->format = $chart['valueFormat'] ?? Chart::FORMAT_NUMBER;
        $this->height = (int) ($chart['height'] ?? self::DEFAULT_HEIGHT);

        $category_key = $chart['categoryKey'] ?? null;

        $this->labels = $category_key
            ? array_map(fn (array $row) => (string) ($row[$category_key] ?? ''), $this->rows)
            : array_map('strval', $chart['labels'] ?? array_column($this->rows, $chart['dateKey'] ?? 'date'));
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $plot = $this->plotArea();
        $scale = new Scale($this->domainValues());

        $model = [
            'width' => $this->width,
            'height' => $this->height,
            'rtl' => $this->rtl,
            'horizontal' => $this->horizontal,
            'variant' => $this->variant,
            'grid' => $this->grid($scale, $plot),
            'axis_labels' => $this->axisLabels($plot),
            'series' => $this->series($scale, $plot),
            'zero' => $this->zeroLine($scale, $plot),
        ];

        return $model;
    }

    /**
     * @return array{left: float, right: float, top: float, bottom: float}
     */
    protected function plotArea(): array
    {
        if ($this->horizontal) {
            $longest = max(array_map('mb_strlen', $this->labels ?: ['']));

            return ['left' => min(180, max(60, $longest * 7 + 12)), 'right' => $this->width - 12, 'top' => 8, 'bottom' => $this->height - 24];
        }

        return ['left' => 52, 'right' => $this->width - 12, 'top' => 8, 'bottom' => $this->height - 28];
    }

    /**
     * Every value the value axis must show, with stacks added up
     *
     * @return list<float>
     */
    protected function domainValues(): array
    {
        $values = [];

        foreach ($this->rows as $row) {
            $positive = 0;
            $negative = 0;

            foreach ($this->keys as $key) {
                $value = $row[$key] ?? null;

                if (! is_numeric($value)) {
                    continue;
                }

                if ($this->stacked && $key !== $this->previous_key) {
                    $value >= 0 ? $positive += $value : $negative += $value;

                    continue;
                }

                $values[] = (float) $value;
            }

            if ($this->stacked) {
                $values[] = $positive;
                $values[] = $negative;
            }
        }

        return $values;
    }

    /**
     * @param  array{left: float, right: float, top: float, bottom: float}  $plot
     * @return list<array<string, mixed>>
     */
    protected function grid(Scale $scale, array $plot): array
    {
        $lines = [];

        foreach ($scale->ticks as $tick) {
            $label = Numbers::tick($tick, $this->format);

            if ($this->horizontal) {
                $x = $scale->position($tick, $plot['left'], $plot['right']);

                $lines[] = [
                    'x1' => $this->x($x), 'x2' => $this->x($x), 'y1' => $plot['top'], 'y2' => $plot['bottom'],
                    'label' => $label, 'label_x' => $this->x($x), 'label_y' => $plot['bottom'] + 16, 'anchor' => 'middle',
                ];

                continue;
            }

            $y = $scale->position($tick, $plot['bottom'], $plot['top']);

            $lines[] = [
                'x1' => $this->x($plot['left']), 'x2' => $this->x($plot['right']), 'y1' => $y, 'y2' => $y,
                'label' => $label, 'label_x' => $this->x($plot['left'] - 8), 'label_y' => $y + 4, 'anchor' => $this->anchor('end'),
            ];
        }

        return $lines;
    }

    /**
     * Category or date labels along the category axis, thinned to fit
     *
     * @param  array{left: float, right: float, top: float, bottom: float}  $plot
     * @return list<array<string, mixed>>
     */
    protected function axisLabels(array $plot): array
    {
        $count = count($this->labels);
        $labels = [];

        if ($this->horizontal) {
            foreach ($this->labels as $i => $label) {
                [$start, $band] = $this->band($i, $plot['top'], $plot['bottom']);

                $labels[] = [
                    'x' => $this->x($plot['left'] - 8), 'y' => $start + $band / 2 + 4,
                    'label' => $label, 'anchor' => $this->anchor('end'),
                ];
            }

            return $labels;
        }

        $every = max(1, (int) ceil($count / self::MAX_X_LABELS));

        foreach ($this->labels as $i => $label) {
            if ($i % $every !== 0) {
                continue;
            }

            $labels[] = [
                'x' => $this->x($this->categoryPosition($i, $plot)), 'y' => $plot['bottom'] + 18,
                'label' => $label, 'anchor' => 'middle',
            ];
        }

        return $labels;
    }

    /**
     * @param  array{left: float, right: float, top: float, bottom: float}  $plot
     * @return array<string, mixed>|null
     */
    protected function zeroLine(Scale $scale, array $plot): ?array
    {
        if ($scale->min >= 0) {
            return null;
        }

        if ($this->horizontal) {
            $x = $this->x($scale->position(0, $plot['left'], $plot['right']));

            return ['x1' => $x, 'x2' => $x, 'y1' => $plot['top'], 'y2' => $plot['bottom']];
        }

        $y = $scale->position(0, $plot['bottom'], $plot['top']);

        return ['x1' => $this->x($plot['left']), 'x2' => $this->x($plot['right']), 'y1' => $y, 'y2' => $y];
    }

    /**
     * The centre of a category (bars) or the position of a point (lines)
     *
     * @param  array{left: float, right: float, top: float, bottom: float}  $plot
     */
    protected function categoryPosition(int $i, array $plot): float
    {
        $count = count($this->rows);

        if ($this->variant === 'bar') {
            [$start, $band] = $this->band($i, $plot['left'], $plot['right']);

            return $start + $band / 2;
        }

        if ($count <= 1) {
            return ($plot['left'] + $plot['right']) / 2;
        }

        return $plot['left'] + $i * ($plot['right'] - $plot['left']) / ($count - 1);
    }

    /**
     * @return array{0: float, 1: float} the band's start and size
     */
    protected function band(int $i, float $from, float $to): array
    {
        $band = ($to - $from) / max(1, count($this->rows));

        return [$from + $i * $band, $band];
    }

    /**
     * @param  array{left: float, right: float, top: float, bottom: float}  $plot
     * @return list<array<string, mixed>>
     */
    protected function series(Scale $scale, array $plot): array
    {
        $series = [];
        $grouped = $this->stacked
            ? array_values(array_unique(array_map(fn ($key) => $key === $this->previous_key ? $key : '__stack', $this->keys)))
            : $this->keys;
        $offsets = array_fill(0, count($this->rows), ['positive' => 0.0, 'negative' => 0.0]);
        $color_index = 0;

        foreach ($this->keys as $key) {
            $is_previous = $key === $this->previous_key;
            $color = $is_previous ? 'var(--stats-previous, var(--muted-foreground))' : "var(--color-{$key}, var(--chart-" . ($color_index++ % 8 + 1) . '))';
            $label = (string) ($this->chart['config'][$key]['label'] ?? $key);
            $stack = $this->stacked && ! $is_previous;
            $points = [];
            $bars = [];

            foreach ($this->rows as $i => $row) {
                $value = $row[$key] ?? null;
                $title = $this->labels[$i] . ' · ' . $label . ': ' . Numbers::format(is_numeric($value) ? $value + 0 : null, $this->format);

                if (! is_numeric($value)) {
                    $points[] = null;

                    continue;
                }

                $value = (float) $value;
                $base = 0.0;

                if ($stack) {
                    $side = $value >= 0 ? 'positive' : 'negative';
                    $base = $offsets[$i][$side];
                    $offsets[$i][$side] += $value;
                }

                if ($this->variant === 'bar') {
                    $bars[] = $this->bar($scale, $plot, $i, array_search($stack ? '__stack' : $key, $grouped, true), count($grouped), $base, $base + $value, $title);

                    continue;
                }

                $x = $this->categoryPosition($i, $plot);
                $y = $scale->position($base + $value, $plot['bottom'], $plot['top']);
                $y0 = $scale->position($base, $plot['bottom'], $plot['top']);

                $points[] = ['x' => $this->x($x), 'y' => round($y, 2), 'y0' => round($y0, 2), 'title' => $title];
            }

            $series[] = [
                'key' => $key,
                'label' => $label,
                'color' => $color,
                'previous' => $is_previous,
                'dashed' => $is_previous && $this->variant !== 'bar',
                'bars' => $bars,
                'points' => array_values(array_filter($points)),
                'line' => $this->variant === 'bar' ? null : static::linePath($points),
                'area' => $this->variant === 'area' && ! $is_previous ? static::areaPath($points) : null,
            ];
        }

        return $series;
    }

    /**
     * @param  array{left: float, right: float, top: float, bottom: float}  $plot
     * @return array<string, mixed>
     */
    protected function bar(Scale $scale, array $plot, int $i, int $slot, int $slots, float $from, float $to, string $title): array
    {
        [$band_from, $band_to] = $this->horizontal ? [$plot['top'], $plot['bottom']] : [$plot['left'], $plot['right']];
        [$start, $band] = $this->band($i, $band_from, $band_to);

        $inner = $band * 0.8;
        $size = $inner / max(1, $slots);
        $offset = $start + ($band - $inner) / 2 + $slot * $size;
        $gap = $slots > 1 ? min(2, $size * 0.1) : 0;

        if ($this->horizontal) {
            $a = $scale->position($from, $plot['left'], $plot['right']);
            $b = $scale->position($to, $plot['left'], $plot['right']);
            $x = min($a, $b);
            $width = abs($b - $a);

            return [
                'x' => round($this->rtl ? $this->width - $x - $width : $x, 2),
                'y' => round($offset + $gap / 2, 2),
                'width' => round($width, 2),
                'height' => round(max(0, $size - $gap), 2),
                'title' => $title,
            ];
        }

        $a = $scale->position($from, $plot['bottom'], $plot['top']);
        $b = $scale->position($to, $plot['bottom'], $plot['top']);
        $width = max(0, $size - $gap);
        $x = $offset + $gap / 2;

        return [
            'x' => round($this->rtl ? $this->width - $x - $width : $x, 2),
            'y' => round(min($a, $b), 2),
            'width' => round($width, 2),
            'height' => round(abs($b - $a), 2),
            'title' => $title,
        ];
    }

    /**
     * A path through the points, broken where a value is missing
     *
     * @param  list<array<string, mixed>|null>  $points
     */
    public static function linePath(array $points): ?string
    {
        $path = '';
        $drawing = false;

        foreach ($points as $point) {
            if ($point === null) {
                $drawing = false;

                continue;
            }

            $path .= ($drawing ? 'L' : 'M') . $point['x'] . ' ' . $point['y'] . ' ';
            $drawing = true;
        }

        return trim($path) ?: null;
    }

    /**
     * The area under each unbroken run of points, down to its base
     *
     * @param  list<array<string, mixed>|null>  $points
     */
    public static function areaPath(array $points): ?string
    {
        $paths = [];
        $run = [];

        foreach ([...$points, null] as $point) {
            if ($point !== null) {
                $run[] = $point;

                continue;
            }

            if ($run !== []) {
                $top = implode(' L', array_map(fn ($p) => $p['x'] . ' ' . $p['y'], $run));
                $bottom = implode(' L', array_map(fn ($p) => $p['x'] . ' ' . $p['y0'], array_reverse($run)));
                $paths[] = "M{$top} L{$bottom} Z";
                $run = [];
            }
        }

        return $paths === [] ? null : implode(' ', $paths);
    }

    /**
     * Mirror an x position for right-to-left
     */
    protected function x(float $x): float
    {
        return round($this->rtl ? $this->width - $x : $x, 2);
    }

    protected function anchor(string $anchor): string
    {
        if (! $this->rtl) {
            return $anchor;
        }

        return match ($anchor) {
            'start' => 'end',
            'end' => 'start',
            default => $anchor,
        };
    }
}
