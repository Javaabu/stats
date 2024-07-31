<?php

namespace Javaabu\Stats\Tests\Feature\Commands;

use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;

class GenerateTimeSeriesStatCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // delete all stats
        $this->deleteDirectory($this->app->path('Stats'));

        // setup skeleton service provider
        $this->copyFile(
            $this->getTestStubPath('Providers/SkeletonAppServiceProvider.php'),
            $this->app->path('Providers/AppServiceProvider.php')
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->app->path('Stats'));

        // setup standard service provider
        $this->copyFile(
            $this->getTestStubPath('Providers/AppServiceProvider.php'),
            $this->app->path('Providers/AppServiceProvider.php')
        );

        parent::tearDown();
    }

    /** @test */
    public function it_can_generate_a_new_sum_stat_file(): void
    {
        $expected_path = $this->app->path('Stats/TimeSeries/PaymentsSum.php');
        $expected_content = $this->getTestStubContents('Stats/TimeSeries/PaymentsSum.php');

        $this->artisan('stats:time-series', ['name' => 'PaymentsSum', 'model' => Payment::class, '--type' => 'sum'])
            ->assertSuccessful();

        $this->assertFileExists($expected_path);

        $actual_content = $this->getGeneratedFileContents($expected_path);
        $this->assertEquals($expected_content, $actual_content);
    }

    /** @test */
    public function it_can_generate_a_new_count_stat_file(): void
    {
        $expected_path = $this->app->path('Stats/TimeSeries/PaymentsCount.php');
        $expected_content = $this->getTestStubContents('Stats/TimeSeries/PaymentsCount.php');

        $this->artisan('stats:time-series', ['name' => 'PaymentsCount', 'model' => Payment::class])
            ->assertSuccessful();

        $this->assertFileExists($expected_path);

        $actual_content = $this->getGeneratedFileContents($expected_path);
        $this->assertEquals($expected_content, $actual_content);
    }

    /** @test */
    public function it_registers_stats_maps(): void
    {
        $expected_path = $this->app->path('Stats/TimeSeries/PaymentsCount.php');
        $expected_content = $this->getTestStubContents('Stats/TimeSeries/PaymentsCount.php');

        $this->artisan('stats:time-series', ['name' => 'PaymentsCount', 'model' => Payment::class])
            ->assertSuccessful();

        $this->assertFileExists($expected_path);

        $actual_content = $this->getGeneratedFileContents($expected_path);
        $this->assertEquals($expected_content, $actual_content);

        $expected_path = $this->app->path('Providers/AppServiceProvider.php');
        $expected_content = $this->getTestStubContents('Providers/StatsAppServiceProvider.php');
        $actual_content = $this->getGeneratedFileContents($expected_path);

        $this->assertEquals($expected_content, $actual_content);
    }
}
