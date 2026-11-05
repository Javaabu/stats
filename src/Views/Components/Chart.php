<?php

namespace Javaabu\Stats\Views\Components;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Stats\Charts\Chart as StatsChart;
use Javaabu\Stats\Support\Numbers;
use Javaabu\Stats\Views\Svg\CartesianChart;
use Javaabu\Stats\Views\Svg\PieGeometry;
use Javaabu\Stats\Views\Svg\SparklineGeometry;

/**
 * Draws any stats chart in Blade, as SVG with Onu's tokens:
 *
 * ```blade
 * <x-stats::chart :chart="$chart" />
 * ```
 *
 * `$chart` is a chart (`TimeSeriesChart`, `CategoryChart`, `PieChart`,
 * `Sparkline`, `StatTile`) or its array.
 */
class Chart extends Component
{
    /** @var array<string, mixed> */
    public array $data;

    public bool $rtl;

    /**
     * @param  StatsChart|array<string, mixed>  $chart
     */
    public function __construct(StatsChart|array $chart, ?bool $rtl = null)
    {
        $this->data = $chart instanceof StatsChart ? $chart->toArray() : $chart;
        $this->rtl = $rtl ?? in_array(app()->getLocale(), (array) config('stats.rtl_locales', []), true);

        Manifest::require('stats::charts');
    }

    public function render(): View
    {
        return match ($this->data['type'] ?? null) {
            'pie' => view('stats::components.charts.pie', $this->pieModel()),
            'sparkline' => view('stats::components.charts.sparkline', $this->sparklineModel()),
            'stat-tile' => view('stats::components.charts.stat-tile', $this->statTileModel()),
            default => view('stats::components.charts.cartesian', $this->cartesianModel()),
        };
    }

    protected function format(int|float|null $value): string
    {
        return Numbers::format($value, $this->data['valueFormat'] ?? StatsChart::FORMAT_NUMBER);
    }

    /**
     * @return array<string, mixed>
     */
    protected function common(): array
    {
        $id = $this->data['id'] ?? 'stats-chart-' . substr(md5(json_encode($this->data)), 0, 8);

        return [
            'id' => $id,
            'title' => (string) ($this->data['title'] ?? ''),
            'description' => $this->data['description'] ?? null,
            'rtl' => $this->rtl,
            'empty' => __('No data to show'),
            'strings' => [
                'view_table' => __('View as table'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function cartesianModel(): array
    {
        $data = $this->data;
        $config = $data['config'] ?? [];
        $rows = $data['data'] ?? [];
        $svg = CartesianChart::make($data, $this->rtl);
        $is_time = ($data['type'] ?? null) === 'time-series';
        $labels = $is_time ? ($data['labels'] ?? array_column($rows, 'date')) : array_column($rows, $data['categoryKey'] ?? 'category');

        $headers = [$is_time ? __('Date') : ($data['categoryLabel'] ?? __('Category'))];

        foreach ($config as $series) {
            $headers[] = $series['label'];
        }

        $comparison = $data['comparison'] ?? null;

        if ($comparison) {
            $headers[] = __('Change');
        }

        $table = [];

        foreach ($rows as $i => $row) {
            $cells = [];

            foreach (array_keys($config) as $key) {
                $cells[] = $this->format(isset($row[$key]) ? $row[$key] + 0 : null);
            }

            if ($comparison) {
                $cells[] = $this->deltaText(static::delta($row[array_key_first($config)] ?? null, $row[$comparison['previousKey']] ?? null));
            }

            $table[] = ['label' => (string) ($labels[$i] ?? ''), 'cells' => $cells];
        }

        return [
            ...$this->common(),
            'svg' => $svg,
            'legend' => count($config) > 1 ? array_map(fn (array $series) => [
                'label' => $series['label'],
                'color' => $series['color'],
                'dashed' => $series['dashed'],
            ], $svg['series']) : [],
            'table' => ['headers' => $headers, 'rows' => $table],
            'totals' => $comparison ? $this->comparisonTotals($rows, array_key_first($config), $comparison) : null,
            'has_data' => $rows !== [],
            'summary' => $this->cartesianSummary($data, $labels),
        ];
    }

    /**
     * The headline totals of a comparison
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $comparison
     * @return array<string, mixed>
     */
    protected function comparisonTotals(array $rows, ?string $current_key, array $comparison): array
    {
        $current = array_sum(array_map(fn ($row) => (float) ($row[$current_key] ?? 0), $rows));
        $previous = array_sum(array_map(fn ($row) => (float) ($row[$comparison['previousKey']] ?? 0), $rows));

        return [
            'current_label' => $comparison['currentLabel'] ?? __('This period'),
            'previous_label' => $comparison['previousLabel'] ?? __('Previous period'),
            'current' => $this->format($current),
            'previous' => $this->format($previous),
            'delta' => $this->deltaModel(static::delta($current, $previous), (bool) ($comparison['positiveIsGood'] ?? true)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $labels
     */
    protected function cartesianSummary(array $data, array $labels): string
    {
        $variant = match ($data['variant'] ?? 'line') {
            'bar' => __('Bar chart'),
            'area' => __('Area chart'),
            default => __('Line chart'),
        };

        $config = $data['config'] ?? [];
        $rows = $data['data'] ?? [];

        $parts = [__(':chart with :series series and :points data points.', [
            'chart' => $variant,
            'series' => count($config),
            'points' => count($rows),
        ])];

        if (($data['type'] ?? null) === 'time-series' && $labels !== []) {
            $parts[] = __('From :from to :to.', ['from' => reset($labels), 'to' => end($labels)]);
        }

        foreach ($config as $key => $series) {
            $values = array_values(array_filter(array_column($rows, $key), 'is_numeric'));

            if ($values === []) {
                continue;
            }

            $parts[] = __(':series: lowest :min, highest :max.', [
                'series' => $series['label'],
                'min' => $this->format(min($values) + 0),
                'max' => $this->format(max($values) + 0),
            ]);
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    protected function pieModel(): array
    {
        $data = $this->data;
        $slices = $data['data'] ?? [];
        $name_key = $data['nameKey'] ?? 'name';
        $value_key = $data['valueKey'] ?? 'value';
        $donut = ($data['variant'] ?? 'pie') === 'donut';
        $values = array_map(fn ($slice) => (float) ($slice[$value_key] ?? 0), $slices);
        $total = array_sum($values);
        $geometry = PieGeometry::slices($values, $donut, $this->rtl);
        $items = [];

        foreach ($slices as $i => $slice) {
            $items[] = [
                'name' => (string) $slice[$name_key],
                'value' => $this->format($values[$i]),
                'share' => Numbers::format($geometry[$i]['share'], StatsChart::FORMAT_PERCENT),
                'path' => $geometry[$i]['path'],
                'color' => 'var(--chart-' . ($i % 8 + 1) . ')',
            ];
        }

        $center = $data['centerLabel'] ?? null;

        if ($donut && $center === null) {
            $center = ['value' => $this->format($total), 'label' => __('Total')];
        }

        $largest = $items[0] ?? null;

        $summary = $largest
            ? __(':chart with :count slices. Total :total. Largest: :name, :share.', [
                'chart' => $donut ? __('Donut chart') : __('Pie chart'),
                'count' => count($items),
                'total' => $this->format($total),
                'name' => $largest['name'],
                'share' => $largest['share'],
            ])
            : '';

        return [
            ...$this->common(),
            'size' => PieGeometry::SIZE,
            'slices' => $items,
            'donut' => $donut,
            'center' => $donut && is_array($center) ? $center : null,
            'name_label' => $data['nameLabel'] ?? __('Category'),
            'value_label' => $data['aggregateLabel'] ?? __('Value'),
            'share_label' => __('Share'),
            'has_data' => $items !== [] && $total > 0,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function sparklineModel(): array
    {
        $values = array_map(fn ($value) => $value === null ? null : $value + 0, $this->data['data'] ?? []);

        return [
            ...$this->common(),
            'svg' => SparklineGeometry::make($values, (int) ($this->data['height'] ?? 32), $this->rtl),
            'variant' => $this->data['variant'] ?? 'line',
            'color' => $this->data['color'] ?? 'var(--chart-1)',
            'label' => static::sparklineSummary((string) ($this->data['label'] ?? $this->data['title'] ?? ''), $values, fn ($value) => $this->format($value)),
        ];
    }

    /**
     * "Daily sign-ups: 8 values, from 12 to 24. Lowest 12, highest 24."
     *
     * @param  list<int|float|null>  $values
     */
    public static function sparklineSummary(string $label, array $values, callable $format): string
    {
        $numbers = array_values(array_filter($values, fn ($value) => $value !== null));

        if ($numbers === []) {
            return $label;
        }

        return __(':label: :count values, from :first to :last. Lowest :min, highest :max.', [
            'label' => $label,
            'count' => count($values),
            'first' => $format($numbers[0]),
            'last' => $format(end($numbers)),
            'min' => $format(min($numbers)),
            'max' => $format(max($numbers)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function statTileModel(): array
    {
        $data = $this->data;
        $value = $data['value'] ?? null;
        $previous = $data['previousValue'] ?? null;
        $sparkline = $data['sparkline'] ?? null;
        $label = (string) ($data['label'] ?? $data['title'] ?? '');

        return [
            ...$this->common(),
            'label' => $label,
            'value' => is_numeric($value) ? $this->format($value + 0) : (string) ($value ?? '—'),
            'delta' => $previous !== null && is_numeric($value)
                ? $this->deltaModel(static::delta($value + 0, $previous + 0), (bool) ($data['positiveIsGood'] ?? true))
                : null,
            'delta_context' => $data['deltaContext'] ?? null,
            'icon' => $data['icon'] ?? null,
            'href' => $data['href'] ?? null,
            'footer' => $data['footer'] ?? null,
            'period' => $data['period'] ?? null,
            'sparkline' => $sparkline ? [
                'svg' => SparklineGeometry::make(array_map(fn ($v) => $v === null ? null : $v + 0, $sparkline), 46, $this->rtl),
                'label' => static::sparklineSummary($label, $sparkline, fn ($v) => $this->format($v)),
            ] : null,
        ];
    }

    /**
     * The change as a ratio, or null when there's nothing to compare with
     */
    public static function delta(int|float|string|null $current, int|float|string|null $previous): ?float
    {
        if (! is_numeric($current) || ! is_numeric($previous) || (float) $previous == 0.0) {
            return null;
        }

        return ((float) $current - (float) $previous) / abs((float) $previous);
    }

    /**
     * The delta's direction, sentiment and text, as Onu's DeltaIndicator shows it
     *
     * @return array<string, string>|null
     */
    protected function deltaModel(?float $delta, bool $positive_is_good = true): ?array
    {
        if ($delta === null) {
            return null;
        }

        $direction = abs($delta) < 0.0005 ? 'flat' : ($delta > 0 ? 'up' : 'down');

        $sentiment = match (true) {
            $direction === 'flat' => 'neutral',
            ($direction === 'up') === $positive_is_good => 'positive',
            default => 'negative',
        };

        $percent = Numbers::format(abs($delta), StatsChart::FORMAT_PERCENT);

        return [
            'direction' => $direction,
            'sentiment' => $sentiment,
            'text' => ($direction === 'down' ? '−' : ($direction === 'up' ? '+' : '')) . $percent,
            'spoken' => match ($direction) {
                'up' => __('Up :percent', ['percent' => $percent]),
                'down' => __('Down :percent', ['percent' => $percent]),
                default => __('No change'),
            },
        ];
    }

    protected function deltaText(?float $delta): string
    {
        $model = $this->deltaModel($delta);

        return $model ? $model['text'] : '—';
    }

    /**
     * Dates for display in the current locale
     */
    public static function date(Carbon $date, string $format): string
    {
        return $date->copy()->locale(app()->getLocale())->isoFormat($format);
    }
}
