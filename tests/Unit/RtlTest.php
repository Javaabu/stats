<?php

namespace Javaabu\Stats\Tests\Unit;

use Illuminate\Support\Facades\File;
use Javaabu\Stats\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RtlTest extends TestCase
{
    #[Test]
    public function it_only_uses_logical_css_in_views_styles_and_react(): void
    {
        $physical = '/(margin|padding|border)-(left|right)\b|text-align:\s*(left|right)|[\s{;](left|right):|\b(ml|mr|pl|pr|left|right|rounded-l|rounded-r|border-l|border-r)-[0-9a-z\[]/';

        $files = [
            ...File::allFiles(__DIR__ . '/../../resources/views'),
            ...File::allFiles(__DIR__ . '/../../resources/css'),
            ...File::allFiles(__DIR__ . '/../../resources/js'),
        ];

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression($physical, $file->getContents(), "{$file->getRelativePathname()} uses physical left/right CSS");
        }
    }
}
