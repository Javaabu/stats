<?php

namespace Javaabu\Stats\Tests\Unit\Generators;

use Javaabu\Stats\Generators\SumStatGenerator;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;

class SumStatsGeneratorTest extends TestCase
{
    /** @test */
    public function it_can_generate_sum_stats_for_an_existing_model(): void
    {
        $generator = new SumStatGenerator('PaymentsSum', Payment::class);

        $expected_content = $this->getTestStubContents('Stats/TimeSeries/PaymentsSum.php');
        $actual_content = $generator->render();

        $this->assertEquals($expected_content, $actual_content);
    }
}
