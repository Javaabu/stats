<?php

namespace Javaabu\Stats\Tests\Feature\Views;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Stats\Charts\CategoryChart;
use Javaabu\Stats\Charts\PieChart;
use Javaabu\Stats\Charts\Sparkline;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BladeChartsTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
        $this->seedPayments();
    }

    protected function range(): ExactDateRange
    {
        return new ExactDateRange('2024-07-01', '2024-07-04 23:59:59');
    }

    protected function renderBlade(string $template, array $data = []): string
    {
        return Blade::render($template, $data);
    }

    #[Test]
    public function it_draws_a_time_series_as_an_accessible_svg_figure(): void
    {
        $chart = TimeSeriesChart::make('payments_count')->mode(TimeSeriesModes::DAY)->range($this->range())->area()->id('payments');

        $html = $this->renderBlade('<x-stats::chart :chart="$chart" />', ['chart' => $chart]);

        $this->assertStringContainsString('<figure class="stats-chart" id="payments" data-slot="chart" data-chart-variant="area" aria-labelledby="payments-title"', $html);
        $this->assertStringContainsString('role="img" aria-labelledby="payments-title" aria-describedby="payments-summary"', $html);
        $this->assertStringContainsString('class="stats-chart__area"', $html);
        $this->assertStringContainsString('class="stats-chart__line"', $html);
        $this->assertStringContainsString('<summary>View as table</summary>', $html);
        $this->assertStringContainsString('<th scope="row">3 Jul 24</th>', $html);
        $this->assertStringContainsString('Area chart with 1 series and 4 data points. From 1 Jul 24 to 4 Jul 24.', $html);
        $this->assertTrue(Manifest::requirements()->has('stats::charts'));
    }

    #[Test]
    public function it_renders_a_chart_echoed_directly(): void
    {
        $html = $this->renderBlade('{{ $chart }}', ['chart' => TimeSeriesChart::make('payments_count')->range($this->range())->bar()]);

        $this->assertStringContainsString('class="stats-chart__bar"', $html);
        $this->assertStringContainsString('data-chart-variant="bar"', $html);
    }

    #[Test]
    public function it_mirrors_charts_and_translates_them_in_dhivehi(): void
    {
        app()->setLocale('dv');

        $chart = TimeSeriesChart::make('payments_count')->mode(TimeSeriesModes::MONTH)->range(new ExactDateRange('2024-06-01', '2024-07-31 23:59:59'))->compare();

        $ltr = $this->renderBlade('<x-stats::chart :chart="$chart" :rtl="false" />', ['chart' => $chart]);
        $rtl = $this->renderBlade('<x-stats::chart :chart="$chart" />', ['chart' => $chart]);

        $this->assertNotEquals($ltr, $rtl);
        $this->assertStringContainsString('ޖޫން', $rtl);
        $this->assertStringContainsString('text-anchor="start" class="stats-chart__tick"', $rtl);
        $this->assertStringContainsString('text-anchor="end" class="stats-chart__tick"', $ltr);
        $this->assertStringContainsString('stroke-dasharray="5 4"', $rtl);
    }

    #[Test]
    public function it_draws_categories_horizontally_and_shows_a_legend_for_several_stats(): void
    {
        $chart = CategoryChart::make()->stats(['payments_by_user', 'payment_amounts_by_user'])->range($this->range())->horizontal();

        $html = $this->renderBlade('<x-stats::category-chart :chart="$chart" />', ['chart' => $chart]);

        $this->assertStringContainsString('data-slot="chart-legend"', $html);
        $this->assertSame(4, substr_count($html, 'class="stats-chart__bar"'));
        $this->assertStringContainsString('>User</th>', $html);
    }

    #[Test]
    public function it_draws_a_donut_with_its_total_in_the_middle(): void
    {
        $html = $this->renderBlade('<x-stats::pie-chart :chart="$chart" />', ['chart' => PieChart::make('payments_by_user')->range($this->range())->donut()]);

        $this->assertStringContainsString('data-chart-variant="donut"', $html);
        $this->assertSame(2, substr_count($html, 'class="stats-chart__slice"'));
        $this->assertStringContainsString('<span class="stats-chart__center-value">5</span>', $html);
        $this->assertStringContainsString('<span class="stats-chart__center-label">Total</span>', $html);
        $this->assertStringContainsString('dir="ltr">60%</span>', $html);
    }

    #[Test]
    public function it_draws_a_sparkline_with_its_summary_as_the_name(): void
    {
        $html = $this->renderBlade('<x-stats::sparkline :chart="$chart" />', ['chart' => Sparkline::make('payments_count')->mode(TimeSeriesModes::DAY)->range($this->range())->area()]);

        $this->assertStringContainsString('role="img" aria-label="Payments: 4 values, from 0 to 2. Lowest 0, highest 3."', $html);
        $this->assertStringContainsString('class="stats-sparkline__area"', $html);
    }

    #[Test]
    public function it_draws_stat_tiles_with_the_change(): void
    {
        $tile = StatTile::make('payments_count')->range($this->range())->compare()->sparkline(TimeSeriesModes::DAY)->href('/payments')->footer('ހަތަރު ދުވަސް');

        $html = $this->renderBlade('<x-stats::stat-tiles :columns="2"><x-stats::stat-tile :tile="$tile" /></x-stats::stat-tiles>', ['tile' => $tile]);

        $this->assertStringContainsString('data-slot="stat-tiles" data-columns="2"', $html);
        $this->assertStringContainsString('<a class="stats-tile"', $html);
        $this->assertStringContainsString('href="/payments"', $html);
        $this->assertStringContainsString('data-slot="stat-tile-value" dir="auto">5</div>', $html);
        $this->assertStringContainsString('data-direction="up" data-sentiment="positive"', $html);
        $this->assertStringContainsString('<span class="stats-sr-only">Up 400%</span>', $html);
        $this->assertStringContainsString('vs previous period', $html);
        $this->assertStringContainsString('ހަތަރު ދުވަސް', $html);
    }

    #[Test]
    public function it_shows_falls_as_good_when_lower_is_better(): void
    {
        $tile = StatTile::make('payments_count')->range(new ExactDateRange('2024-06-29', '2024-06-30 23:59:59'))->compare(new ExactDateRange('2024-07-03', '2024-07-04 23:59:59'))->positiveIsGood(false);

        $html = $this->renderBlade('<x-stats::stat-tile :tile="$tile" />', ['tile' => $tile]);

        $this->assertStringContainsString('data-direction="down" data-sentiment="positive"', $html);
        $this->assertStringContainsString('Down 80%', $html);
    }

    #[Test]
    public function it_says_when_there_is_no_data(): void
    {
        $html = $this->renderBlade('<x-stats::chart :chart="$chart" />', ['chart' => PieChart::make('payments_by_user')->range(new ExactDateRange('2020-01-01', '2020-01-02'))]);

        $this->assertStringContainsString('data-slot="chart-empty">No data to show</p>', $html);
    }

    #[Test]
    public function it_draws_a_chart_from_an_array(): void
    {
        $html = $this->renderBlade('<x-stats::chart :chart="$chart" />', ['chart' => [
            'type' => 'category',
            'title' => 'ރަށްތައް',
            'valueFormat' => 'currency',
            'data' => [['category' => 'މާލެ', 'amount' => 1250.5], ['category' => 'އައްޑޫ', 'amount' => -20]],
            'config' => ['amount' => ['label' => 'ފައިސާ']],
            'categoryKey' => 'category',
            'variant' => 'bar',
        ]]);

        $this->assertStringContainsString('ރަށްތައް', $html);
        $this->assertStringContainsString('MVR 1,250.50', $html);
        $this->assertStringContainsString('class="stats-chart__zero"', $html);
    }
}
