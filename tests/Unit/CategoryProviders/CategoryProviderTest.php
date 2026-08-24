<?php

namespace Javaabu\Stats\Tests\Unit\CategoryProviders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Javaabu\Stats\CategoryProviders\ArrayCategoryProvider;
use Javaabu\Stats\CategoryProviders\EloquentCategoryProvider;
use Javaabu\Stats\CategoryProviders\EnumCategoryProvider;
use Javaabu\Stats\Contracts\CategoryProvider;
use Javaabu\Stats\Support\CategoryProviderFactory;
use Javaabu\Stats\Tests\TestCase;
use Javaabu\Stats\Tests\TestSupport\Enums\PaymentStatus;
use Javaabu\Stats\Tests\TestSupport\Models\CategoricalSearchUser;
use Javaabu\Stats\Tests\TestSupport\Models\CategoryProviderUser;
use Javaabu\Stats\Tests\TestSupport\Models\User;

class CategoryProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_provider_from_an_array(): void
    {
        $provider = CategoryProviderFactory::make([
            10 => 'North',
            20 => 'South',
        ]);

        $this->assertInstanceOf(CategoryProvider::class, $provider);
        $this->assertSame('Category', $provider->getCategoricalStatsCategoryTitle());
        $this->assertSame('Category ID', $provider->getCategoricalStatsCategoryIdTitle());
        $this->assertEquals([
            ['id' => 20, 'label' => 'South'],
        ], $provider->getCategoricalStatsItems([20])->all());
        $this->assertEquals([
            ['id' => 10, 'label' => 'North'],
        ], $provider->searchCategoricalStatsItems('nor')->getCollection()->all());
    }

    public function test_it_can_create_a_provider_from_a_collection(): void
    {
        $provider = CategoryProviderFactory::make(collect([
            ['id' => 'a', 'label' => 'Alpha'],
            ['id' => 'b', 'label' => 'Beta'],
        ]));

        $this->assertEquals([
            ['id' => 'a', 'label' => 'Alpha'],
            ['id' => 'b', 'label' => 'Beta'],
        ], $provider->getCategoricalStatsItems()->all());
    }

    public function test_it_can_create_a_provider_from_an_enum(): void
    {
        $provider = CategoryProviderFactory::make(PaymentStatus::class);

        $this->assertEquals([
            ['id' => 'pending', 'label' => 'Awaiting Payment'],
            ['id' => 'paid', 'label' => 'Paid'],
        ], $provider->getCategoricalStatsItems()->all());
        $this->assertSame('Payment Status', $provider->getCategoricalStatsCategoryTitle());
        $this->assertSame('Payment Status ID', $provider->getCategoricalStatsCategoryIdTitle());
    }

    public function test_it_can_create_a_searchable_provider_from_an_eloquent_model(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        $provider = CategoryProviderFactory::make(User::class);

        $this->assertEquals(['Alice'], $provider->searchCategoricalStatsItems('ali')->getCollection()->pluck('label')->all());
        $this->assertSame('User', $provider->getCategoricalStatsCategoryTitle());
        $this->assertSame('User ID', $provider->getCategoricalStatsCategoryIdTitle());
    }

    public function test_the_categorical_stats_search_scope_takes_precedence(): void
    {
        User::factory()->create(['name' => 'Alice', 'email' => 'special@example.com']);
        User::factory()->create(['name' => 'Special Name', 'email' => 'other@example.com']);

        $provider = CategoryProviderFactory::make(CategoricalSearchUser::class);

        $this->assertEquals(['Alice'], $provider->searchCategoricalStatsItems('special')->getCollection()->pluck('label')->all());
    }

    public function test_an_eloquent_model_can_use_the_prefixed_provider_trait(): void
    {
        User::factory()->create(['name' => 'Alice', 'email' => 'special@example.com']);
        User::factory()->create(['name' => 'Special Name', 'email' => 'other@example.com']);

        $provider = CategoryProviderFactory::make(CategoryProviderUser::class);

        $this->assertInstanceOf(CategoryProviderUser::class, $provider);
        $this->assertEquals(['Alice'], $provider->searchCategoricalStatsItems('special')->getCollection()->pluck('label')->all());
        $this->assertSame('Account', $provider->getCategoricalStatsCategoryTitle());
        $this->assertSame('Account Number', $provider->getCategoricalStatsCategoryIdTitle());
    }

    public function test_it_paginates_array_providers(): void
    {
        $provider = CategoryProviderFactory::make([
            10 => 'North',
            20 => 'South',
            30 => 'Central',
        ]);

        $this->assertCategoryProviderPage($provider, 20);
    }

    public function test_it_paginates_collection_providers(): void
    {
        $provider = CategoryProviderFactory::make(collect([
            ['id' => 'a', 'label' => 'Alpha'],
            ['id' => 'b', 'label' => 'Beta'],
            ['id' => 'c', 'label' => 'Gamma'],
        ]));

        $this->assertCategoryProviderPage($provider, 'b');
    }

    public function test_it_paginates_enum_providers(): void
    {
        $provider = CategoryProviderFactory::make(PaymentStatus::class);

        $this->assertCategoryProviderPage($provider, 'paid', 2);
    }

    public function test_it_paginates_eloquent_providers(): void
    {
        User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        User::factory()->create(['name' => 'Charlie']);
        $provider = CategoryProviderFactory::make(User::class);

        $this->assertCategoryProviderPage($provider, $bob->id);
    }

    public function test_it_can_sort_array_and_collection_providers_by_field_and_direction(): void
    {
        $array_provider = new ArrayCategoryProvider([
            10 => 'North',
            20 => 'South',
            30 => 'Central',
        ]);
        $collection_provider = new ArrayCategoryProvider(collect([
            ['id' => 'a', 'label' => 'Alpha'],
            ['id' => 'b', 'label' => 'Beta'],
            ['id' => 'c', 'label' => 'Gamma'],
        ]));

        $this->assertEquals(
            ['South', 'North', 'Central'],
            $array_provider->sortCategoricalStatsItemsBy('label', 'desc')
                ->getCategoricalStatsItems()
                ->pluck('label')
                ->all()
        );
        $this->assertEquals(
            ['a', 'b', 'c'],
            $collection_provider->sortCategoricalStatsItemsBy('id')
                ->getCategoricalStatsItems()
                ->pluck('id')
                ->all()
        );
    }

    public function test_it_can_sort_in_memory_providers_using_a_callback(): void
    {
        $provider = new ArrayCategoryProvider([
            10 => 'North',
            20 => 'South',
            30 => 'Central',
        ]);

        $provider->sortCategoricalStatsItemsUsing(
            fn (array $left, array $right) => strlen($left['label']) <=> strlen($right['label'])
        );

        $this->assertEquals(
            ['North', 'South', 'Central'],
            $provider->getCategoricalStatsItems()->pluck('label')->all()
        );
    }

    public function test_it_can_sort_enum_providers(): void
    {
        $provider = new EnumCategoryProvider(PaymentStatus::class);

        $this->assertEquals(
            ['paid', 'pending'],
            $provider->sortCategoricalStatsItemsBy('label', 'desc')
                ->getCategoricalStatsItems()
                ->pluck('id')
                ->all()
        );
    }

    public function test_it_can_sort_eloquent_providers_by_field_and_direction(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Bob']);

        $provider = new EloquentCategoryProvider(User::class);

        $this->assertEquals(
            ['Charlie', 'Bob', 'Alice'],
            $provider->sortCategoricalStatsItemsBy('name', 'desc')
                ->getCategoricalStatsItems()
                ->pluck('label')
                ->all()
        );
    }

    public function test_it_preserves_sorting_defined_on_an_eloquent_query(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Bob']);

        $provider = CategoryProviderFactory::make(User::query()->orderByDesc('name'));

        $this->assertEquals(
            ['Charlie', 'Bob', 'Alice'],
            $provider->getCategoricalStatsItems()->pluck('label')->all()
        );
        $this->assertEquals(
            ['Charlie', 'Bob', 'Alice'],
            $provider->searchCategoricalStatsItems()->getCollection()->pluck('label')->all()
        );
    }

    public function test_it_can_sort_eloquent_providers_using_a_query_callback(): void
    {
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Bob']);

        $provider = new EloquentCategoryProvider(User::class);
        $provider->sortCategoricalStatsItemsUsing(fn ($query) => $query->orderByDesc('name'));

        $this->assertEquals(
            ['Charlie', 'Bob', 'Alice'],
            $provider->getCategoricalStatsItems()->pluck('label')->all()
        );
    }

    protected function assertCategoryProviderPage(CategoryProvider $provider, int|string $expected_id, int $total = 3): void
    {
        Paginator::currentPageResolver(fn () => 2);

        try {
            $paginator = $provider->searchCategoricalStatsItems(per_page: 1);
        } finally {
            Paginator::currentPageResolver(fn ($page_name = 'page') => (int) request()->input($page_name, 1));
        }

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame(1, $paginator->perPage());
        $this->assertSame($total, $paginator->total());
        $this->assertEquals($expected_id, $paginator->getCollection()->first()['id']);
    }
}
