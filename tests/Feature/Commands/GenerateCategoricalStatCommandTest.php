<?php

namespace Javaabu\Stats\Tests\Feature\Commands;

use Illuminate\Database\Eloquent\Relations\Relation;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Models\Payment;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class GenerateCategoricalStatCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->deleteDirectory($this->app->path('Stats'));
        $this->copyFile(
            $this->getTestStubPath('Providers/CategoricalSkeletonAppServiceProvider.php'),
            $this->app->path('Providers/AppServiceProvider.php')
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->app->path('Stats'));
        $this->copyFile(
            $this->getTestStubPath('Providers/AppServiceProvider.php'),
            $this->app->path('Providers/AppServiceProvider.php')
        );

        parent::tearDown();
    }

    public function test_it_can_generate_a_count_categorical_stat(): void
    {
        $expected_path = $this->app->path('Stats/Categorical/PaymentsByUser.php');
        $expected_content = $this->getTestStubContents('Stats/Categorical/PaymentsByUser.php');
        $morph_map = Relation::morphMap();

        Relation::morphMap(['user' => User::class], false);

        try {
            $this->artisan('stats:categorical', [
                'name' => 'PaymentsByUser',
                'model' => Payment::class,
                'category_model' => User::class,
                'category_id_field' => 'user_id',
            ])->assertSuccessful();

            $this->assertFileExists($expected_path);
            $this->assertEquals($expected_content, $this->getGeneratedFileContents($expected_path));
        } finally {
            Relation::morphMap($morph_map, false);
        }
    }

    public function test_it_can_generate_a_sum_categorical_stat(): void
    {
        $expected_path = $this->app->path('Stats/Categorical/PaymentAmountsByUser.php');
        $expected_content = $this->getTestStubContents('Stats/Categorical/PaymentAmountsByUser.php');

        $this->artisan('stats:categorical', [
            'name' => 'PaymentAmountsByUser',
            'model' => Payment::class,
            'category_model' => User::class,
            'category_id_field' => 'payments.user_id',
            '--type' => 'sum',
        ])->assertSuccessful();

        $this->assertFileExists($expected_path);
        $this->assertEquals($expected_content, $this->getGeneratedFileContents($expected_path));
    }

    public function test_it_registers_the_generated_categorical_stat(): void
    {
        $this->artisan('stats:categorical', [
            'name' => 'PaymentsByUser',
            'model' => Payment::class,
            'category_model' => User::class,
            'category_id_field' => 'user_id',
        ])->assertSuccessful();

        $expected_content = $this->getTestStubContents('Providers/CategoricalStatsAppServiceProvider.php');
        $actual_content = $this->getGeneratedFileContents($this->app->path('Providers/AppServiceProvider.php'));

        $this->assertEquals($expected_content, $actual_content);
    }

    public function test_it_rejects_an_invalid_categorical_stat_type(): void
    {
        $this->artisan('stats:categorical', [
            'name' => 'PaymentsByUser',
            'model' => Payment::class,
            'category_model' => User::class,
            'category_id_field' => 'user_id',
            '--type' => 'average',
        ])->assertFailed();

        $this->assertFileDoesNotExist($this->app->path('Stats/Categorical/PaymentsByUser.php'));
    }
}
