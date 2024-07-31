<?php

namespace Javaabu\Stats\Tests\Unit\Generators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Stats\Generators\CountStatGenerator;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;

class CountStatsGeneratorTest extends TestCase
{
    /** @test */
    public function it_can_generate_count_stats_for_an_existing_model(): void
    {
        $generator = new CountStatGenerator('PaymentsCount', Payment::class);

        $expected_content = $this->getTestStubContents('Stats/TimeSeries/PaymentsCount.php');
        $actual_content = $generator->render();

        $this->assertEquals($expected_content, $actual_content);
    }
}
