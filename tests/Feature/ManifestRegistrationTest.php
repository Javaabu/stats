<?php

namespace Javaabu\Stats\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Manifest;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Blade apps build the chart styles, never the React pages, so
 * `manifest:check` must not ask them for React packages (Javaabu/manifest#17)
 */
class ManifestRegistrationTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(base_path('package.json'));

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $dependencies
     * @return array<string, array<string, mixed>>
     */
    protected function check(array $dependencies): array
    {
        File::put(base_path('package.json'), json_encode(['devDependencies' => $dependencies]));

        Artisan::call('manifest:check', ['--json' => true]);

        return collect(json_decode(Artisan::output(), true))->keyBy('name')->all();
    }

    #[Test]
    public function it_declares_the_react_packages_for_the_pages_only(): void
    {
        $package = app(Manifest::class)->package('stats');

        $this->assertSame([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertSame([], $package->npmPeerDependencies(NpmScopes::ASSETS));

        $this->assertSame(['@javaabu/forms', '@javaabu/onu', 'recharts'], array_keys($package->npmDependencies(NpmScopes::PAGES)));
        $this->assertSame(['react', 'react-dom', '@inertiajs/react'], array_keys($package->npmPeerDependencies(NpmScopes::PAGES)));
    }

    #[Test]
    public function it_asks_blade_apps_only_for_the_build_tools(): void
    {
        $rows = $this->check(['vite' => '^7.0', 'laravel-vite-plugin' => '^2.0']);

        $this->assertArrayNotHasKey('recharts', $rows);

        // Only the tools that build the chart styles
        $needed = collect($rows)->filter(fn (array $row) => in_array('stats', $row['needed_by']))->keys()->sort()->values()->all();

        $this->assertSame(['laravel-vite-plugin', 'vite'], $needed);
    }

    #[Test]
    public function it_asks_inertia_apps_for_what_the_pages_need(): void
    {
        $rows = $this->check(['@inertiajs/react' => '^2.0']);

        foreach (['@javaabu/forms', '@javaabu/onu', 'recharts', 'react', 'react-dom', '@inertiajs/react'] as $name) {
            $this->assertContains('stats', $rows[$name]['needed_by'], $name);
            $this->assertContains('pages', $rows[$name]['scopes'], $name);
        }
    }
}
