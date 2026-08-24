<?php

namespace Javaabu\Stats\Tests\Unit;

use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\StatListReturnType;
use Javaabu\Stats\Formatters\Categorical\DefaultCategoricalStatsFormatter;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Stats\Categorical\PaymentsByUser;

class CategoricalStatsTest extends TestCase
{
    public function test_it_can_register_and_create_categorical_stats(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);

        $stats = CategoricalStats::createFromMetric('payments_by_user', PresetDateRanges::LAST_7_DAYS);

        $this->assertInstanceOf(PaymentsByUser::class, $stats);
        $this->assertEquals('payments_by_user', $stats->metric());
        $this->assertEquals(PresetDateRanges::LAST_7_DAYS, $stats->getDateRange());
    }

    public function test_it_only_lists_metrics_that_support_the_current_filters(): void
    {
        CategoricalStats::register([
            'payments_by_user' => PaymentsByUser::class,
        ], false);

        $this->assertEquals(
            ['payments_by_user'],
            CategoricalStats::metricsThatAllowFilters('user', null, StatListReturnType::METRIC)
        );
        $this->assertEmpty(CategoricalStats::allowedMetrics(['unknown']));
    }

    public function test_it_can_register_and_create_a_formatter(): void
    {
        CategoricalStats::registerFormatters([
            'default' => DefaultCategoricalStatsFormatter::class,
        ], false);

        $formatter = CategoricalStats::createFromFormat('default');

        $this->assertInstanceOf(DefaultCategoricalStatsFormatter::class, $formatter);
        $this->assertEquals('default', $formatter->getName());
    }
}
