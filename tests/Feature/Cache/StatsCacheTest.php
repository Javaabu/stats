<?php

namespace Javaabu\Stats\Tests\Feature\Cache;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Javaabu\Stats\Cache\StatsCache;
use Javaabu\Stats\Charts\StatTile;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Facades\Stats;
use Javaabu\Stats\Jobs\WarmStatsCache;
use Javaabu\Stats\StatsManager;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\Tests\Concerns\InteractsWithStats;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;
use Javaabu\Stats\Tests\TestSupport\Models\CachedPayment;
use PHPUnit\Framework\Attributes\Test;

class StatsCacheTest extends TestCase
{
    use InteractsWithStats;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerTestStats();
        $this->seedPayments();
    }

    protected function tile(): StatTile
    {
        return StatTile::make('payments_count')->range(new ExactDateRange('2024-07-01', '2024-07-04 23:59:59'));
    }

    protected function addPayment(): void
    {
        PaymentFactory::new()->withUser()->create(['paid_at' => '2024-07-04 10:00:00']);
    }

    #[Test]
    public function it_caches_chart_data_until_the_metric_is_forgotten(): void
    {
        $this->assertEquals(5, $this->tile()->toArray()['value']);

        $this->addPayment();

        $this->assertEquals(5, $this->tile()->toArray()['value']);

        Stats::forget('payments_count');

        $this->assertEquals(6, $this->tile()->toArray()['value']);
    }

    #[Test]
    public function it_forgets_every_metric_when_flushed(): void
    {
        $this->tile()->toArray();
        $this->addPayment();

        Stats::flush();

        $this->assertEquals(6, $this->tile()->toArray()['value']);
    }

    #[Test]
    public function it_does_not_forget_other_metrics(): void
    {
        $this->tile()->toArray();
        $this->addPayment();

        Stats::forget('payments_sum');

        $this->assertEquals(5, $this->tile()->toArray()['value']);
    }

    #[Test]
    public function it_skips_the_cache_when_turned_off(): void
    {
        $this->tile()->withoutCache()->toArray();
        $this->addPayment();

        $this->assertEquals(6, $this->tile()->withoutCache()->toArray()['value']);
        $this->assertFalse($this->tile()->withoutCache()->isWarm());

        config()->set('stats.cache.enabled', false);
        $this->addPayment();

        $this->assertEquals(7, $this->tile()->toArray()['value']);
    }

    #[Test]
    public function it_keeps_past_periods_longer_than_current_ones(): void
    {
        $cache = app(StatsCache::class);

        $this->assertEquals(600, $cache->ttlFor(now()->endOfDay()));
        $this->assertEquals(86400, $cache->ttlFor(now()->subDay()->endOfDay()));

        config()->set('stats.cache.ttl.current', 60);

        $this->assertEquals(60, $cache->ttlFor(null));
    }

    #[Test]
    public function it_uses_the_charts_own_ttl(): void
    {
        $tile = $this->tile()->cache(minutes: 5);

        $tile->toArray();
        $this->addPayment();
        $this->assertEquals(5, $this->tile()->cache(minutes: 5)->toArray()['value']);

        $this->travel(6)->minutes();

        $this->assertEquals(6, $this->tile()->cache(minutes: 5)->toArray()['value']);
    }

    #[Test]
    public function it_keys_the_cache_by_what_the_chart_shows(): void
    {
        $this->tile()->toArray();

        $this->assertTrue($this->tile()->isWarm());
        $this->assertFalse($this->tile()->compare()->isWarm());
        $this->assertFalse(StatTile::make('payments_count')->range(PresetDateRanges::TODAY)->isWarm());

        app()->setLocale('dv');
        $this->assertFalse($this->tile()->isWarm());
    }

    #[Test]
    public function it_invalidates_the_stats_of_models_with_the_trait(): void
    {
        $this->tile()->toArray();

        CachedPayment::query()->create(['amount' => 5, 'paid_at' => '2024-07-04 11:00:00']);

        $this->assertEquals(6, $this->tile()->toArray()['value']);

        $this->tile()->toArray();
        CachedPayment::query()->latest('id')->first()->delete();

        $this->assertEquals(5, $this->tile()->toArray()['value']);
    }

    #[Test]
    public function it_warms_the_registered_charts(): void
    {
        Stats::warm([$this->tile()]);
        Stats::warm(fn () => TimeSeriesChart::make('payments_sum')->range(PresetDateRanges::LAST_7_DAYS));

        $this->assertFalse($this->tile()->isWarm());

        $this->artisan('stats:warm')
            ->expectsOutputToContain('Warmed 2 charts.')
            ->assertSuccessful();

        $this->assertTrue($this->tile()->isWarm());
        $this->assertTrue(TimeSeriesChart::make('payments_sum')->range(PresetDateRanges::LAST_7_DAYS)->isWarm());
    }

    #[Test]
    public function it_refreshes_what_is_cached_when_warming(): void
    {
        Stats::warm($this->tile());

        $this->tile()->toArray();
        $this->addPayment();

        (new WarmStatsCache)->handle(app(StatsManager::class));

        $this->assertEquals(6, $this->tile()->toArray()['value']);
    }

    #[Test]
    public function it_can_queue_the_warming_job(): void
    {
        Queue::fake();
        config()->set('stats.cache.warm.queue', 'stats');

        $this->artisan('stats:warm --queue --flush')->assertSuccessful();

        Queue::assertPushedOn('stats', WarmStatsCache::class);
    }
}
