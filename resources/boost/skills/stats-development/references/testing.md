# Testing Stat Repositories

Every stat class in `app/Stats/TimeSeries/` gets a PHPUnit test in `tests/Feature/Stats/TimeSeries/{StatName}Test.php`. See `SKILL.md` → *Testing the Stat* for the canonical file scaffolding (namespace, imports, base class, `RefreshDatabase` trait) and the DAY example. This doc covers the patterns beyond that — other time modes, sum stats, filters, authorization, and pitfalls. All patterns are lifted from the package's own tests in `tests/Feature/Repositories/`.

## Time Travel

Preset date ranges (`TODAY`, `LAST_7_DAYS`, `THIS_YEAR`, etc.) are relative to "now". Always call `$this->travelTo(...)` at the top of each test so the assertions stay stable over time. For HOUR mode tests, freeze the time too: `$this->travelTo('2024-07-22 8:06 PM')`.

## Count Stat Tests — Other Time Modes

The aggregate field is `count`. The time field is named after the mode (`hour`, `week`, `month`, `year`). The DAY case lives in `SKILL.md`.

### Hour

```php
public function test_it_returns_hourly_counts(): void
{
    $this->travelTo('2024-07-22 8:06 PM');

    Order::factory()->count(5)->create(['created_at' => '2024-07-22 7:06 PM']);
    Order::factory()->count(2)->create(['created_at' => '2024-07-22 8:06 PM']);

    $data = (new OrdersCount(PresetDateRanges::TODAY))
        ->results(TimeSeriesModes::HOUR)
        ->toArray();

    $this->assertEquals([
        ['count' => 5, 'hour' => '2024-07-22 19:00'],
        ['count' => 2, 'hour' => '2024-07-22 20:00'],
    ], $data);
}
```

### Week

Week values are formatted `YYYYWW` (year + ISO week number).

```php
public function test_it_returns_weekly_counts(): void
{
    $this->travelTo('2024-07-22 8:06 PM');

    Order::factory()->count(5)->create(['created_at' => '2024-07-22 7:06 PM']);
    Order::factory()->count(2)->create(['created_at' => '2024-01-03 7:06 PM']);

    $data = (new OrdersCount(PresetDateRanges::THIS_YEAR))
        ->results(TimeSeriesModes::WEEK)
        ->toArray();

    $this->assertEquals([
        ['count' => 2, 'week' => '202401'],
        ['count' => 5, 'week' => '202430'],
    ], $data);
}
```

### Month

Month values are formatted `YYYY, MM`.

```php
public function test_it_returns_monthly_counts(): void
{
    $this->travelTo('2024-07-22 8:06 PM');

    Order::factory()->count(5)->create(['created_at' => '2024-07-22 7:06 PM']);
    Order::factory()->count(2)->create(['created_at' => '2024-01-03 7:06 PM']);

    $data = (new OrdersCount(PresetDateRanges::THIS_YEAR))
        ->results(TimeSeriesModes::MONTH)
        ->toArray();

    $this->assertEquals([
        ['count' => 2, 'month' => '2024, 01'],
        ['count' => 5, 'month' => '2024, 07'],
    ], $data);
}
```

### Year

```php
public function test_it_returns_yearly_counts(): void
{
    $this->travelTo('2024-07-22 8:06 PM');

    Order::factory()->count(5)->create(['created_at' => '2024-07-22 7:06 PM']);
    Order::factory()->count(2)->create(['created_at' => '2023-01-03 7:06 PM']);

    $data = (new OrdersCount(PresetDateRanges::LIFETIME))
        ->results(TimeSeriesModes::YEAR)
        ->toArray();

    $this->assertEquals([
        ['count' => 2, 'year' => '2023'],
        ['count' => 5, 'year' => '2024'],
    ], $data);
}
```

## Sum Stat Tests

Aggregate field is `total` (not `count`). Seed the column named in `getFieldToSum()` — e.g. `amount` for `PaymentAmounts`.

```php
public function test_it_returns_daily_sums(): void
{
    $this->travelTo('2024-07-04');

    Payment::factory()->count(5)->create(['paid_at' => '2024-07-03', 'amount' => 10]);
    Payment::factory()->count(2)->create(['paid_at' => '2024-07-04', 'amount' => 5]);

    $data = (new PaymentAmounts(PresetDateRanges::LAST_7_DAYS))
        ->results(TimeSeriesModes::DAY)
        ->toArray();

    $this->assertEquals([
        ['total' => 50, 'day' => '2024-07-03'],
        ['total' => 10, 'day' => '2024-07-04'],
    ], $data);
}
```

The structure is identical to the Count variant — only the aggregate field name and the seeded data change.

## Filter Tests

For every entry in `allowedFilters()`, add a test that seeds two cohorts and confirms only the filtered cohort appears in results. Pass filters as the second constructor argument.

```php
public function test_it_filters_orders_by_customer(): void
{
    $alice = Customer::factory()->create();
    $bob = Customer::factory()->create();

    Order::factory()->count(2)->create([
        'customer_id' => $alice->id,
        'created_at' => '2024-07-04 00:00:00',
    ]);
    Order::factory()->count(5)->create([
        'customer_id' => $bob->id,
        'created_at' => '2024-07-07 00:00:00',
    ]);

    $data = (new OrdersCount(PresetDateRanges::LIFETIME, ['customer' => $bob->id]))
        ->results(TimeSeriesModes::DAY)
        ->toArray();

    $this->assertEquals([
        ['count' => 5, 'day' => '2024-07-07'],
    ], $data);
}
```

The first argument to the filter array is the **filter key** declared in `allowedFilters()` (`'customer'`), not the underlying column (`customer_id`).

## Authorization Tests

Only needed if the stat overrides `canView(?Authorizable $user)` — the package already tests the default `view_stats` permission. When you do override, e.g.:

```php
public function canView(?Authorizable $user): bool
{
    return $user?->can('view_orders_stats') ?? false;
}
```

Add two assertions covering both branches:

```php
$granted = User::factory()->create();
$granted->givePermissionTo('view_orders_stats');
$this->assertTrue((new OrdersCount(PresetDateRanges::TODAY))->canView($granted));

$denied = User::factory()->create();
$this->assertFalse((new OrdersCount(PresetDateRanges::TODAY))->canView($denied));
```

## Common Pitfalls

- **Custom date column.** When `getDateFieldName()` is overridden (e.g. `'paid_at'`), factory rows must seed that column — not `created_at`.
- **Timezone mismatch.** Aggregation runs in the DB session's timezone. If `config('app.timezone')` differs from the DB timezone, hour/day boundaries shift. For deterministic tests, pin `app.timezone` to `UTC` in `phpunit.xml`.
- **Preset range inclusivity.** `LAST_7_DAYS` includes today. `THIS_YEAR` includes the entire current calendar year. Seed data outside the range to confirm exclusion works.
- **Ordering.** Results are ordered by the time bucket ascending. `assertEquals([...])` must follow that order; use `assertEqualsCanonicalizing` only if order genuinely doesn't matter.
