<?php

namespace Javaabu\Stats\Tests\Unit;

use Illuminate\Support\Facades\File;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationsTest extends TestCase
{
    /**
     * Every literal __('...') in the package's PHP and Blade
     *
     * @return list<string>
     */
    protected function usedKeys(): array
    {
        $keys = ['View stats', 'Stats', 'Primary Period - :date_from - :date_to', 'Comparison Period - :date_from - :date_to'];

        $files = [...File::allFiles(__DIR__ . '/../../src'), ...File::allFiles(__DIR__ . '/../../resources/views')];

        foreach ($files as $file) {
            preg_match_all("/__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $file->getContents(), $matches);

            foreach ($matches[1] as $key) {
                $keys[] = str_replace("\\'", "'", $key);
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, string>
     */
    protected function translations(string $locale): array
    {
        return json_decode(File::get(__DIR__ . "/../../lang/{$locale}.json"), true, flags: JSON_THROW_ON_ERROR);
    }

    #[Test]
    public function it_has_english_and_dhivehi_for_every_string(): void
    {
        $en = $this->translations('en');
        $dv = $this->translations('dv');

        foreach ($this->usedKeys() as $key) {
            $this->assertArrayHasKey($key, $en, "lang/en.json is missing [{$key}]");
            $this->assertArrayHasKey($key, $dv, "lang/dv.json is missing [{$key}]");
        }

        $this->assertEquals(array_keys($en), array_keys($dv));
    }

    #[Test]
    public function it_keeps_the_placeholders_in_dhivehi(): void
    {
        foreach ($this->translations('dv') as $key => $value) {
            preg_match_all('/:[a-z_]+/', $key, $expected);
            preg_match_all('/:[a-z_]+/', $value, $actual);

            sort($expected[0]);
            sort($actual[0]);

            $this->assertEquals($expected[0], $actual[0], "The Dhivehi of [{$key}] has different placeholders");
            $this->assertMatchesRegularExpression('/\p{Thaana}/u', $value, "[{$key}] isn't in Thaana");
        }
    }

    #[Test]
    public function it_translates_the_stats_strings_in_dhivehi(): void
    {
        app()->setLocale('dv');

        $this->assertEquals('ފާއިތުވި 7 ދުވަސް', __('Last 7 days'));
        $this->assertEquals('ޖަދުވަލެއްގެ ގޮތުގައި ބަލާ', __('View as table'));
    }
}
